<?php

namespace Webbhuset\CollectorBankCheckout\Checkout\Order;

class Manager
{
    protected $cartManagement;
    protected $config;
    protected $orderRepository;
    protected $quoteRepository;
    protected $collectorAdapter;

    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->cartManagement = $cartManagement;
        $this->quoteRepository = $quoteRepository;
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
        $orderId = $this->cartManagement->placeOrder($quoteId);

        return $orderId;
    }

    public function cancelOrder($incrementOrderId)
    {

    }

    public function acknowledgeOrder($incrementOrderId)
    {
    }

    public function holdOrder($incrementOrderId)
    {
    }
}
