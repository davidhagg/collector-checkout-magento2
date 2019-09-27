<?php

namespace Webbhuset\CollectorBankCheckout\Plugin;

class SetNeedsUpdateOnItemChange
{
    protected $quoteRepository;
    protected $quoteDataHandler;
    protected $config;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorBankCheckout\Config\Config $config
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->config = $config;
    }

    public function afterRemoveItem(
        \Magento\Checkout\Model\Cart $subject,
        $result
    ) {
        $subject->getQuote()->setNeedsCollectorUpdate(true);

        return $result;
    }

    public function afterUpdateItems(
        \Magento\Checkout\Model\Cart $subject,
        $result
    ) {
        $subject->getQuote()->setNeedsCollectorUpdate(true);

        return $result;
    }
}
