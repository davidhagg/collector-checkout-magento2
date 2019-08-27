<?php

namespace Webbhuset\CollectorBankCheckout\Checkout\Order;

use CollectorBank\CheckoutSDK\Checkout\Purchase\Result as PurchaseResult;

class Manager
{
    protected $cartManagement;
    protected $orderRepository;
    protected $quoteRepository;
    protected $collectorAdapter;
    protected $orderHandler;
    protected $searchCriteriaBuilder;
    protected $config;
    protected $orderManagement;
    protected $quoteManagement;
    protected $orderManager;
    protected $registry;

    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Webbhuset\CollectorBankCheckout\Data\OrderHandler $orderHandler,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Webbhuset\CollectorBankCheckout\AdapterFactory $collectorAdapter,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Magento\Framework\Registry $registry
    ) {
        $this->cartManagement        = $cartManagement;
        $this->collectorAdapter      = $collectorAdapter;
        $this->orderRepository       = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->config                = $config;
        $this->orderManagement       = $orderManagement;
        $this->orderHandler          = $orderHandler;
        $this->quoteManagement       = $quoteManagement;
        $this->orderManager          = $orderManager;
        $this->registry              = $registry;
    }

    /**
     * Create order from quote id and return order id
     *
     * @param $quoteId
     * @return int orderId
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOrder(\Magento\Quote\Model\Quote $quote): string
    {
        $order = $this->quoteManagement->submit($quote);
        return $order->getIncrementId();
    }

    public function deleteOrder($order)
    {
        $this->registry->register('isSecureArea', 'true');
        $this->orderRepository->delete($order);
        $this->registry->unregister('isSecureArea', 'true');
    }

    public function removeOrderIfExists($reference)
    {
        try {
            $order = $this->orderManager->create()->getOrderByPublicToken($reference);

            $order = $this->orderHandler->setPrivateId($order, "");
            $order = $this->orderHandler->setPublicToken($order, "");
            $order = $this->orderHandler->setStoreId($order, "");

            $this->orderRepository->save($order);
            $this->orderManagement->cancel($order->getId());

            $this->deleteOrder($order);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        }
    }
    /**
     * Create order from quote id and return order id
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function notificationCallbackHandler(\Magento\Sales\Api\Data\OrderInterface $order): array
    {
        $collectorBankPrivateId = $this->orderHandler->getPrivateId($order);

        $checkoutAdapter = $this->collectorAdapter->create();
        $checkoutData = $checkoutAdapter->acquireCheckoutInformation($collectorBankPrivateId);

        $paymentResult = $checkoutData->getPurchase()->getResult()->getResult();

        $result = "";

        if (\Magento\Sales\Model\Order::STATE_CANCELED == $order->getState()) {
            return [
                'message' => 'Order is cancelled, order status can not be changed'
            ];
        }

        switch ($paymentResult) {
            case PurchaseResult::PRELIMINARY:
                $result = $this->acknowledgeOrder($order, $checkoutData);
                break;

            case PurchaseResult::ON_HOLD:
                $result = $this->holdOrder($order, $checkoutData);
                break;

            case PurchaseResult::REJECTED:
                $result = $this->cancelOrder($order, $checkoutData);
                break;

            case PurchaseResult::ACTIVATED:
                $result = $this->activateOrder($order, $checkoutData);
                break;
        }
        $this->orderRepository->save($order);

        return $result;
    }

    public function acknowledgeOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ):array {
        $orderStatusBefore = $this->orderManagement->getStatus($order->getId());
        $orderStatusAfter  = $this->config->create()->getOrderStatusAcknowledged();

        if ($orderStatusAfter == $orderStatusBefore) {
            return [
                'message' => 'Order status already set to: ' . $orderStatusAfter
            ];
        }

        $this->unHoldOrderIfHolded($order);

        $this->addPaymentInformation(
            $order->getPayment(),
            $checkoutData->getPurchase()
        );

        $this->updateOrderStatus(
            $order,
            $orderStatusAfter,
            \Magento\Sales\Model\Order::STATE_PROCESSING
        );

        $this->orderManagement->notify($order->getEntityId());

        return [
            'order_status_before' => $orderStatusBefore,
            'order_status_after' => $orderStatusAfter
        ];
    }

    private function addPaymentInformation(
        \Magento\Sales\Api\Data\OrderPaymentInterface $payment,
        \CollectorBank\CheckoutSDK\Checkout\Purchase $purchaseData
    ) {
        $info = [
            'method_title'            => \Webbhuset\CollectorBankCheckout\Gateway\Config::PAYMENT_METHOD_NAME,
            'payment_name'            => $purchaseData->getPaymentName(),
            'amount_to_pay'           => $purchaseData->getAmountToPay(),
            'invoice_delivery_method' => $purchaseData->getInvoiceDeliveryMethod(),
            'purchase_identifier'     => $purchaseData->getPurchaseIdentifier(),
            'result'                  => $purchaseData->getResult()->getResult(),
        ];
        $payment->setAdditionalInformation($info);

        $payment->authorize(true, $purchaseData->getAmountToPay());
    }

    public function holdOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ):array {
        $orderStatusBefore = $this->orderManagement->getStatus($order->getId());
        $orderStatusAfter  = $this->config->create()->getOrderStatusHolded();

        if ($orderStatusBefore == $orderStatusAfter) {
            return [
                'message' => 'Order status already set to: ' . $orderStatusAfter
            ];
        }

        $this->orderManagement->hold($order->getId());

        $this->updateOrderStatus(
            $order,
            $orderStatusAfter,
            \Magento\Sales\Model\Order::STATE_HOLDED
        );

        return [
            'order_status_before' => $orderStatusBefore,
            'order_status_after' => $this->orderManagement->getStatus($order->getId())
        ];
    }

    public function unHoldOrderIfHolded(
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        if (\Magento\Sales\Model\Order::STATE_HOLDED == $order->getState()) {
            $this->orderManagement->unHold($order->getId());
        }
    }

    public function cancelOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ):array {
        $orderStatusBefore = $this->orderManagement->getStatus($order->getId());
        $orderStatusAfter  = $this->config->create()->getOrderStatusHolded();

        if ($orderStatusBefore == $orderStatusAfter) {
            return [
                'message' => 'Order status already set to: ' . $orderStatusAfter
            ];
        }
        $this->unHoldOrderIfHolded($order);

        $this->orderManagement->cancel($order->getId());

        $this->updateOrderStatus(
            $order,
            $this->config->create()->getOrderStatusDenied(),
            \Magento\Sales\Model\Order::STATE_CANCELED
        );

        return [
            'order_status_before' => $orderStatusBefore,
            'order_status_after' => $this->orderManagement->getStatus($order->getId())
        ];
    }

    public function activateOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ):array {
        $orderStatusBefore = $this->orderManagement->getStatus($order->getId());

        // Do something here?
        // Should this invoice the order and capture offline?

        return [
            'order_status_before' => $orderStatusBefore,
            'order_status_after' => $this->orderManagement->getStatus($order->getId())
        ];
    }

    public function getOrderByPublicToken($publicToken): \Magento\Sales\Api\Data\OrderInterface
    {
        return $this->getColumnFromSalesOrder("collectorbank_public_id", $publicToken);
    }

    public function getOrderByIncrementId($incrementOrderId): \Magento\Sales\Api\Data\OrderInterface
    {
        return $this->getColumnFromSalesOrder("increment_id", $incrementOrderId);
    }

    public function getOrderByQuoteId($quoteId): \Magento\Sales\Api\Data\OrderInterface
    {
        return $this->getColumnFromSalesOrder("quote_id", $quoteId);
    }

    private function getColumnFromSalesOrder($column, $value): \Magento\Sales\Api\Data\OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($column, $value, 'eq')->create();
        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();

        if (sizeof($orderList) == 0) {
            throw new \Magento\Framework\Exception\NoSuchEntityException();
        }

        return reset($orderList);
    }

    private function getIncrementIdByOrderId($orderId)
    {
        $order = $this->orderRepository->get($orderId);

        return $order->getIncrementId();
    }

    private function updateOrderStatus($order, $status, $state)
    {
        $order->setState($state)
            ->setStatus($status);

        return $this;
    }
}
