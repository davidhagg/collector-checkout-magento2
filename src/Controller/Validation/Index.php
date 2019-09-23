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
    protected $logger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResult,
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Webbhuset\CollectorBankCheckout\Checkout\Quote\ManagerFactory $quoteManager,
        \Webbhuset\CollectorBankCheckout\Checkout\Customer\ManagerFactory $customerManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger
    ) {
        $this->orderManager    = $orderManager;
        $this->jsonResult      = $jsonResult;
        $this->customerManager = $customerManager;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManager    = $quoteManager;
        $this->logger          = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $reference = $this->getRequest()->getParam('reference');
        $jsonResult = $this->jsonResult->create();
        try {
            $orderManager = $this->orderManager->create();
            $customerManager = $this->customerManager->create();
            $quoteManager = $this->quoteManager->create();

            $orderManager->removeNewOrdersByPublicToken($reference);
            $quote = $quoteManager->getQuoteByPublicToken($reference);
            $customerManager->handleCustomerOnQuote($quote);

            $orderId = $orderManager->createOrder($quote);

            $response = [
                'orderReference' => $orderId
            ];
            $jsonResult->setHttpResponseCode(200);
        } catch (\Magento\Framework\Exception\CouldNotSaveException $e) {
            $response = [
                'title' => __('Could not save order'),
                'message' => __($e->getMessage())
            ];
            $jsonResult->setHttpResponseCode(404);
            $this->logger->addCritical(
                "Validation callback CouldNotSaveException. qouteId: {$quote->getId()} " .
                " orderId: {$quote->getReservedOrderId()} publicToken: $reference. {$e->getMessage()}"
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $response = [
                'title' => __('Cart not found'),
                'message' => __($e->getMessage())
            ];
            $jsonResult->setHttpResponseCode(404);
            $this->logger->addCritical(
                "Validation callback NoSuchEntityException publicToken: $reference. {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            $response = [
                'title' => __('Exception'),
                'message' => __($e->getMessage())
            ];
            $jsonResult->setHttpResponseCode(404);
            $this->logger->addCritical(
                "Validation callback Unrecoverable exception publicToken: $reference. {$e->getMessage()}"
            );
        }

        $jsonResult->setHeader("Content-Type", "application/json", true);
        $jsonResult->setData($response);

        return $jsonResult;
    }
}
