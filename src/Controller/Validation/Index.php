<?php

namespace Webbhuset\CollectorCheckout\Controller\Validation;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorCheckout\Controller\Validation
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory
     */
    protected $orderManager;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonResult;
    /**
     * @var \Webbhuset\CollectorCheckout\Checkout\Customer\ManagerFactory
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
     * @var \Webbhuset\CollectorCheckout\Checkout\Quote\ManagerFactory
     */
    protected $quoteManager;
    /**
     * @var \Webbhuset\CollectorCheckout\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Webbhuset\CollectorCheckout\QuoteComparerFactory
     */
    protected $quoteComparer;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context                             $context
     * @param \Magento\Framework\Controller\Result\JsonFactory                  $jsonResult
     * @param \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory    $orderManager
     * @param \Webbhuset\CollectorCheckout\Checkout\Quote\ManagerFactory    $quoteManager
     * @param \Webbhuset\CollectorCheckout\Checkout\Customer\ManagerFactory $customerManager
     * @param \Magento\Quote\Api\CartRepositoryInterface                        $quoteRepository
     * @param \Webbhuset\CollectorCheckout\Logger\Logger                    $logger
     * @param \Webbhuset\CollectorCheckout\QuoteComparerFactory             $quoteComparer
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResult,
        \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Webbhuset\CollectorCheckout\Checkout\Quote\ManagerFactory $quoteManager,
        \Webbhuset\CollectorCheckout\Checkout\Customer\ManagerFactory $customerManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger,
        \Webbhuset\CollectorCheckout\QuoteComparerFactory $quoteComparer
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
        } catch (\Webbhuset\CollectorCheckout\Exception\QuoteNotInSyncException $e) {
            $response = [
                'title' => __('Cart not in sync'),
                'message' => __('Please refresh the page and try again.')
            ];
            $jsonResult->setHttpResponseCode(404);
            $this->logger->addCritical(
                "Cart not in sync on callback QuoteNotInSyncException publicToken: $reference. {$e->getMessage()}"
            );
        } catch (\Throwable $e) {
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
