<?php

namespace Webbhuset\CollectorBankCheckout\Observer;

class UpdateOrderItemsChanged implements \Magento\Framework\Event\ObserverInterface
{
    protected $config;
    protected $adapter;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        \Webbhuset\CollectorBankCheckout\Adapter $adapter
    ) {
        $this->config = $config;
        $this->adapter = $adapter;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->getIsActive()) {
            return;
        }

        if ($observer->getCart()) {
            $quote = $observer->getCart()->getQuote();
            $quote->setNeedsCollectorUpdate(true);
        }
    }

    protected function isInitialized($quote)
    {
        return null !== $quote->getCollectorbankPrivateId();
    }
}
