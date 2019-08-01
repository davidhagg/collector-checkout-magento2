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
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Webbhuset\CollectorBankCheckout\AdapterFactory $collectorAdapter,
        \Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config

    ) {
        $this->cartManagement        = $cartManagement;
        $this->quoteRepository       = $quoteRepository;
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
    public function createOrder($quoteId): int
    {
        $this->quoteRepository->get($quoteId);
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
        $orderId = $order->getEntityId();
        // get collector bank reference id
        $collectorBankPrivateId = $order->getCollectorbankPrivateId();

        $adapter = $this->collectorAdapter->create();
        $checkoutData = $adapter->acquireCheckoutInformation($collectorBankPrivateId);
        $purchaseResult = $checkoutData->getPurchase()->getResult();

        switch ($purchaseResult) {
            case PurchaseResult::PRELIMINARY:
                $this->acknowledgeOrder($orderId);
                break;

            case PurchaseResult::ON_HOLD:
                $this->holdOrder($orderId);
                break;

            case PurchaseResult::REJECTED:
                $this->cancelOrder($orderId);
                break;

            case PurchaseResult::ACTIVATED:
                $this->activateOrder($orderId);
                break;
        }
    }

    public function acknowledgeOrder($orderId)
    {
        $order = $this->orderRepository->get($orderId);

        $this->updateOrderStatus(
            $order,
            $this->config->create()->getOrderStatusAcknowledged(),
            Magento\Sales\Model\Order::STATE_PROCESSING
        );
    }

    public function holdOrder($orderId)
    {
        $order = $this->orderRepository->get($orderId);

        $this->updateOrderStatus(
            $order,
            $this->config->create()->getOrderStatusHolded(),
            Magento\Sales\Model\Order::STATE_HOLDED
        );
    }

    public function cancelOrder($orderId)
    {
        $order = $this->orderRepository->get($orderId);

        $this->updateOrderStatus(
            $order,
            $this->config->create()->getOrderStatusDenied(),
            Magento\Sales\Model\Order::STATE_CANCELED
        );
    }

    public function activateOrder($orderId)
    {
        // Should this invoice the order and capture offline?
    }

    private function getOrderByIncrementId($incrementOrderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementOrderId, 'eq')->create();
        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();

        if (!isset($orderList[0])) {
            throw new \Magento\Framework\Exception\NoSuchEntityException();
        }

        return $orderList[0];
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

        $this->orderRepository->save($order);
    }
}
