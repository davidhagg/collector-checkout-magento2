<?php

namespace Webbhuset\CollectorBankCheckout\Observer;

class QuoteSaveObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $config;
    protected $adpter;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        \Webbhuset\CollectorBankCheckout\Adapter $adapter
    ) {
        $this->config = $config;
        $this->adapter = $adapter;
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
            || !$this->config->getIsActive()
            || !$this->isInitialized($quote)
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
