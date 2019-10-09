<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Update;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorBankCheckout\Controller\Update
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Adapter
     */
    protected $collectorAdapter;
    /**
     * @var \Webbhuset\CollectorBankCheckout\QuoteConverter
     */
    protected $quoteConverter;
    /**
     * @var \Webbhuset\CollectorBankCheckout\QuoteUpdater
     */
    protected $quoteUpdater;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Logger\Logger
     */
    protected $logger;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context            $context
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Webbhuset\CollectorBankCheckout\Adapter         $collectorAdapter
     * @param \Webbhuset\CollectorBankCheckout\QuoteConverter  $quoteConverter
     * @param \Webbhuset\CollectorBankCheckout\QuoteUpdater    $quoteUpdater
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Webbhuset\CollectorBankCheckout\Logger\Logger   $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorBankCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorBankCheckout\QuoteUpdater $quoteUpdater,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession   = $checkoutSession;
        $this->collectorAdapter  = $collectorAdapter;
        $this->quoteConverter    = $quoteConverter;
        $this->quoteUpdater      = $quoteUpdater;
        $this->logger            = $logger;

        return parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $quote = $this->checkoutSession->getQuote(); // get id from session or url?

        $publicId = $this->getRequest()->getParam('quoteid');
        $eventName = $this->getRequest()->getParam('event');

        if (!$quote->getId()) {
            $result->setHttpResponseCode(404);
            $this->logger->addCritical(
                "Quote updater controller - Quote not found quoteId: $publicId event: $eventName"
            );
            return $result->setData(['message' => __('Quote not found')]);
        }

        $quote = $this->collectorAdapter->synchronize($quote);
        $shippingAddress = $quote->getShippingAddress();

        $result->setData(
            [
                'postcode' => $shippingAddress->getPostcode(),
                'region' => $shippingAddress->getRegion(),
                'country_id' => $shippingAddress->getCountryId(),
                'shipping_method' => $shippingAddress->getShippingMethod(),
                'updated' => true
            ]
        );

        return $result;
    }
}
