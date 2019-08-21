<?php

namespace Webbhuset\CollectorBankCheckout\Checkout\Order;

use CollectorBank\CheckoutSDK\Checkout\Purchase\Result as PurchaseResult;

class Manager
{
    protected $cartManagement;
    protected $orderRepository;
    protected $quoteRepository;
    protected $collectorAdapter;
    protected $searchCriteriaBuilder;
    protected $config;

    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Webbhuset\CollectorBankCheckout\AdapterFactory $collectorAdapter,
        \Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config
    ) {
        $this->cartManagement        = $cartManagement;
        $this->collectorAdapter      = $collectorAdapter;
        $this->orderRepository       = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->config                = $config;
    }

    /**
     * Create order from quote id and return order id
     *
     * @param $quoteId
     * @return int orderId
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createOrder($quoteId): string
    {
        $orderId = $this->cartManagement->placeOrder($quoteId);

        return $this->getIncrementIdByOrderId($orderId);
    }

    /**
     * Create order from quote id and return order id
     *
     * @param $incrementOrderId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function notificationCallbackHandler($incrementOrderId)
    {
        $order = $this->getOrderByIncrementId($incrementOrderId);

        $collectorBankPrivateId = $order->getCollectorbankPrivateId();

        $checkoutAdapter = $this->collectorAdapter->create();
        $checkoutData = $checkoutAdapter->acquireCheckoutInformation($collectorBankPrivateId);

        $paymentResult = $checkoutData->getPurchase()->getResult()->getResult();

        switch ($paymentResult) {
            case PurchaseResult::PRELIMINARY:
                $this->acknowledgeOrder($order, $checkoutData);
                break;

            case PurchaseResult::ON_HOLD:
                $this->holdOrder($order, $checkoutData);
                break;

            case PurchaseResult::REJECTED:
                $this->cancelOrder($order, $checkoutData);
                break;

            case PurchaseResult::ACTIVATED:
                $this->activateOrder($order, $checkoutData);
                break;
        }
        $this->orderRepository->save($order);
    }

    public function acknowledgeOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        $this->addPaymentInformation(
            $order->getPayment(),
            $checkoutData->getPurchase()
        );

        $this->updateOrderStatus(
            $order,
            $this->config->create()->getOrderStatusAcknowledged(),
            \Magento\Sales\Model\Order::STATE_PROCESSING
        );
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
    ) {
        $this->updateOrderStatus(
            $order,
            $this->config->create()->getOrderStatusHolded(),
            \Magento\Sales\Model\Order::STATE_HOLDED
        );
    }

    public function cancelOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        $this->updateOrderStatus(
            $order,
            $this->config->create()->getOrderStatusDenied(),
            \Magento\Sales\Model\Order::STATE_CANCELED
        );
    }

    public function activateOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        // Should this invoice the order and capture offline?
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
