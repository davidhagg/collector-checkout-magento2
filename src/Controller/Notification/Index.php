<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Notification;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $orderManager;
    protected $jsonResult;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResult
    ) {
        $this->orderManager = $orderManager;
        $this->jsonResult   = $jsonResult;

        parent::__construct($context);
    }

    public function execute()
    {
        $jsonResult = $this->jsonResult->create();

        $orderManager = $this->orderManager->create();

        $incrementOrderId = $this->getRequest()->getParam('orderid');
        try {
            $result = $orderManager->notificationCallbackHandler($incrementOrderId);

            $jsonResult->setHttpResponseCode(200);
            $jsonResult->setData($result);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $jsonResult->setHttpResponseCode(404);
            $jsonResult->setData(['message' => __('Entity not found')]);

        } catch (\Exception $e) {
            $jsonResult->setHttpResponseCode(404);
            $jsonResult->setData(['message' => __($e->message())]);

        }
        return $jsonResult;
    }
}
