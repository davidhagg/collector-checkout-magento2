<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Validation;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $orderManager;
    protected $jsonResult;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResult,
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager
    ) {
        $this->orderManager = $orderManager;
        $this->jsonResult = $jsonResult;

        parent::__construct($context);
    }

    public function execute()
    {
        $orderManager = $this->orderManager->create();
        $jsonResult   = $this->jsonResult->create();

        $qouteId = $this->getRequest()->getParam('quoteid');

        try {
            $orderId = $orderManager->createOrder($qouteId);

            $response = [
                'orderReference' => $orderId
            ];
        } catch (\Magento\Framework\Exception\CouldNotSaveException $e) {
            $response = [
                'title' => __('Could not save order'),
                'message' => __($e->getMessage())
            ];
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $response = [
                'title' => __('Cart not found'),
                'message' => __($e->getMessage())
            ];
        }
        $jsonResult->setHeader("Content-Type", "application/json", true);
        $jsonResult->setData($response);

        return $jsonResult;
    }
}
