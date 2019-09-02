<?php

namespace Webbhuset\CollectorBankCheckout;

class QuoteComparer
{
    protected $adapter;
    protected $quoteConverter;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\AdapterFactory $adapter,
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter
    ) {
        $this->adapter        = $adapter;
        $this->quoteConverter = $quoteConverter;
    }

    public function isQuoteInSync(
        \Magento\Quote\Model\Quote $quote
    ): bool {
        $adapter = $this->adapter->create();
        $checkoutData = $adapter->acquireCheckoutInformationFromQuote($quote);

        return $this->isQuoteItemsInSync($quote, $checkoutData);
    }

    public function isQuoteItemsInSync(
        \Magento\Quote\Model\Quote $quote,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        $checkoutItems = $checkoutData->getCart()->getItems();

        $collectorCartItems = [];
        foreach ($checkoutItems as $item) {
            $collectorCartItems[] = $item->toArray();
        }
        array_walk($collectorCartItems, [$this,'removeColumns']);

        $cartItems = $this->quoteConverter->getCart($quote)->toArray();
        $cartItems = $cartItems['items'];
        array_walk($cartItems, [$this,'removeColumns']);

        return serialize($collectorCartItems) == serialize($cartItems);
    }

    private function removeColumns(&$item, $key)
    {
        unset($item['requiresElectronicId'], $item['sku']);
    }
}
