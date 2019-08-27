<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Validation;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $orderManager;
    protected $jsonResult;
    protected $customerManager;
    protected $checkoutSession;
    protected $quoteRepository;
    protected $quoteManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResult,
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Webbhuset\CollectorBankCheckout\Checkout\Quote\ManagerFactory $quoteManager,
        \Webbhuset\CollectorBankCheckout\Checkout\Customer\ManagerFactory $customerManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->orderManager    = $orderManager;
        $this->jsonResult      = $jsonResult;
        $this->customerManager = $customerManager;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManager    = $quoteManager;

        parent::__construct($context);
    }

    public function execute()
    {
        $reference = $this->getRequest()->getParam('reference');
        try {
            $orderManager = $this->orderManager->create();
            $customerManager = $this->customerManager->create();
            $quoteManager = $this->quoteManager->create();

            $orderManager->removeOrderIfExists($reference);

            $quote = $quoteManager->getQuoteByPublicToken($reference);
            $quoteManager->activateQuote($quote);

            $customerManager->handleCustomerOnQuote($quote);

            $orderId = $orderManager->createOrder($quote);
            $quoteManager->activateQuote($quote);

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
        $jsonResult = $this->jsonResult->create();
        $jsonResult->setHeader("Content-Type", "application/json", true);
        $jsonResult->setData($response);

        return $jsonResult;
    }
}
