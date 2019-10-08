<?php

namespace Webbhuset\CollectorBankCheckout\Cron;

/**
 * Class RemovePendingOrders
 *
 * @package Webbhuset\CollectorBankCheckout\Cron
 */
class RemovePendingOrders
{
    /**
     * @var \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory
     */
    protected $orderManager;

    /**
     * RemovePendingOrders constructor.
     *
     * @param \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager
     */
    public function __construct(
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager
    ) {
        $this->orderManager = $orderManager;
    }

    /**
     *
     */
    public function execute()
    {
        $orderManager = $this->orderManager->create();

        $orders = $orderManager->getPendingCollectorBankOrders();

        foreach ($orders as $order) {
            $orderManager->removeOrderIfExists($order);
        }
    }
}
