<?php

namespace Webbhuset\CollectorBankCheckout\Cron;

class RemovePendingOrders
{
    protected $orderManager;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager
    ) {
        $this->orderManager = $orderManager;
    }

    public function execute()
    {
        $orderManager = $this->orderManager->create();

        $orders = $orderManager->getPendingCollectorBankOrders();

        foreach ($orders as $order) {
            $orderManager->removeOrderIfExists($order);
        }
    }
}
