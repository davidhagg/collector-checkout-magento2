<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Validation;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorBankCheckout\Controller\Validation
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory
     */
    protected $orderManager;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonResult;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Checkout\Customer\ManagerFactory
     */
    protected $customerManager;
    /**
     * @var
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Checkout\Quote\ManagerFactory
     */
    protected $quoteManager;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Webbhuset\CollectorBankCheckout\QuoteComparerFactory
     */
    protected $quoteComparer;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context                             $context
     * @param \Magento\Framework\Controller\Result\JsonFactory                  $jsonResult
     * @param \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory    $orderManager
     * @param \Webbhuset\CollectorBankCheckout\Checkout\Quote\ManagerFactory    $quoteManager
     * @param \Webbhuset\CollectorBankCheckout\Checkout\Customer\ManagerFactory $customerManager
     * @param \Magento\Quote\Api\CartRepositoryInterface                        $quoteRepository
     * @param \Webbhuset\CollectorBankCheckout\Logger\Logger                    $logger
     * @param \Webbhuset\CollectorBankCheckout\QuoteComparerFactory             $quoteComparer
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResult,
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Webbhuset\CollectorBankCheckout\Checkout\Quote\ManagerFactory $quoteManager,
        \Webbhuset\CollectorBankCheckout\Checkout\Customer\ManagerFactory $customerManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger,
        \Webbhuset\CollectorBankCheckout\QuoteComparerFactory $quoteComparer
    ) {
        $this->orderManager    = $orderManager;
        $this->jsonResult      = $jsonResult;
        $this->customerManager = $customerManager;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManager    = $quoteManager;
        $this->logger          = $logger;
        $this->quoteComparer   = $quoteComparer;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $reference = $this->getRequest()->getParam('reference');
        $jsonResult = $this->jsonResult->create();
        try {
            $quoteManager = $this->quoteManager->create();
            $quote = $quoteManager->getQuoteByPublicToken($reference);

            $this->quoteComparer->create()->isQuoteInSync($quote);

            $orderManager = $this->orderManager->create();
            $customerManager = $this->customerManager->create();

            $orderManager->removeNewOrdersByPublicToken($reference);
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
        } catch (\Webbhuset\CollectorBankCheckout\Exception\QuoteNotInSyncException $e) {
            $response = [
                'title' => __('Cart not in sync'),
                'message' => __('Please refresh the page and try again.')
            ];
            $jsonResult->setHttpResponseCode(404);
            $this->logger->addCritical(
                "Cart not in sync on callback QuoteNotInSyncException publicToken: $reference. {$e->getMessage()}"
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
