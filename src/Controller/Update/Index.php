<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Update;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $checkoutSession;
    protected $collectorAdapter;
    protected $config;
    protected $quoteConverter;
    protected $quoteUpdater;
    protected $logger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorBankCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorBankCheckout\QuoteUpdater $quoteUpdater,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession   = $checkoutSession;
        $this->collectorAdapter  = $collectorAdapter;
        $this->config            = $config;
        $this->quoteConverter    = $quoteConverter;
        $this->quoteUpdater      = $quoteUpdater;
        $this->logger            = $logger;

        return parent::__construct($context);
    }

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

        $result->setData(
            [
                'updated' => true
            ]
        );

        return $result;
    }
}
