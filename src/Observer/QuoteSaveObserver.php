<?php

namespace Webbhuset\CollectorBankCheckout\Observer;

class QuoteSaveObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $config;
    protected $adapter;
    protected $quoteValidator;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        \Webbhuset\CollectorBankCheckout\Adapter $adapter,
        \Webbhuset\CollectorBankCheckout\QuoteValidator $quoteValidator
    ) {
        $this->config = $config;
        $this->adapter = $adapter;
        $this->quoteValidator = $quoteValidator;
    }

    /**
     * On quote save, update collector order
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();

        if (
            !$quote->getIsActive()
            || !$quote->getNeedsCollectorUpdate()
            || !$this->config->getIsActive()
            || !$this->isInitialized($quote)
            || !$this->quoteValidator->canUseCheckout($quote)
        ) {
            return;
        }

        $this->adapter->updateCart($quote);
        $this->adapter->updateFees($quote);
    }

    protected function isInitialized($quote)
    {
        return null !== $quote->getCollectorbankPrivateId();
    }
}
