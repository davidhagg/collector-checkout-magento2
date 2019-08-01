<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Notification;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $orderManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager
    ) {
        $this->orderManager = $orderManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $orderManager = $this->orderManager->create();

        $incrementOrderId = $this->getRequest()->getParam('orderid');
        try {
            $orderManager->notificationCallbackHandler($incrementOrderId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // log something ? got a notification callback, no matching order id.
        }

    }
}
