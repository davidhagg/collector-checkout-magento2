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

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorBankCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorBankCheckout\QuoteUpdater $quoteUpdater,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory    = $resultJsonFactory;
        $this->checkoutSession      = $checkoutSession;
        $this->collectorAdapter     = $collectorAdapter;
        $this->config               = $config;
        $this->quoteConverter       = $quoteConverter;
        $this->quoteUpdater         = $quoteUpdater;

        return parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $quote = $this->checkoutSession->getQuote(); // get id from session or url?

        $publicId = $this->getRequest()->getParam('quoteid');
        $eventName = $this->getRequest()->getParam('event');

        // Log event and id

        if (!$quote->getId()) {
            $result->setHttpResponseCode(404);

            return $result->setData(['message' => __('Quote not found')]);
        }

        $oldFees = $this->quoteConverter->getFees($quote);
        $oldCart = $this->quoteConverter->getCart($quote);
        $checkoutData = $this->collectorAdapter->acquireCheckoutInformationFromQuote($quote);

        $quote = $this->quoteUpdater->setQuoteData($quote, $checkoutData);
        $quote->collectTotals()
            ->save();

        $newFees = $this->quoteConverter->getFees($quote);
        if ($oldFees != $newFees) {
            $this->collectorAdapter->updateFees($quote);
        }

        $newCart = $this->quoteConverter->getCart($quote);
        if ($oldCart != $newCart) {
            $this->collectorAdapter->updateCart($quote);
        }

        $result->setData(
            [
                'updated' => true
            ]
        );

        return $result;
    }
}
