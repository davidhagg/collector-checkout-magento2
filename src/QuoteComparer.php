<?php

namespace Webbhuset\CollectorBankCheckout;

use Magento\Framework\Phrase;
use Webbhuset\CollectorBankCheckout\Exception\QuoteNotInSyncException;

class QuoteComparer
{
    protected $adapter;
    protected $quoteConverter;
    protected $config;
    protected $storeManager;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\AdapterFactory $adapter,
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->adapter        = $adapter;
        $this->quoteConverter = $quoteConverter;
        $this->config         = $config;
        $this->storeManager   = $storeManager;
    }

    public function isQuoteInSync(
        \Magento\Quote\Api\Data\CartInterface $quote
    ): bool {
        $adapter = $this->adapter->create();
        $checkoutData = $adapter->acquireCheckoutInformationFromQuote($quote);

        $grandTotalInSync = $this->isGrandTotalSync($quote, $checkoutData);
        if (!$grandTotalInSync) {
            throw new QuoteNotInSyncException(new Phrase('Grand total not in sync'));
        }

        $cartInSync = $this->isCartItemsInSync($quote, $checkoutData);
        if (!$cartInSync) {
            throw new QuoteNotInSyncException(new Phrase('Items not in sync'));
        }

        return true;
    }

    public function isGrandTotalSync(
        \Magento\Quote\Model\Quote $quote,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        $grandTotalCeil = ceil($quote->getGrandTotal());
        $collectorTotalCeil = ceil($this->calculateCollectorTotal($checkoutData));

        $grandTotalRound = round($quote->getGrandTotal());
        $collectorTotalRound = round($this->calculateCollectorTotal($checkoutData));

        return ($grandTotalCeil == $collectorTotalCeil)
            || ($grandTotalRound == $collectorTotalRound);
    }

    public function isCartItemsInSync(
        \Magento\Quote\Model\Quote $quote,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        $collectorCartItems = $this->getCollectorCartAsArray($checkoutData);
        $cartItems = $this->getQuoteItemsAsArray($quote);

        array_walk($collectorCartItems, [$this, 'serializeElements']);
        array_walk($cartItems, [$this, 'serializeElements']);

        return empty(array_diff($collectorCartItems, $cartItems));
    }

    public function isCurrencyMatching()
    {
        $collectorCurrency = $this->config->getCurrency();
        $storeCurrency = $this->storeManager->getStore()->getCurrentCurrencyCode();

        return ($collectorCurrency == $storeCurrency);
    }

    private function getQuoteItemsAsArray(
        \Magento\Quote\Model\Quote $quote
    ) {
        $cartItems = $this->quoteConverter->getCart($quote)->toArray();
        $cartItems = $cartItems['items'];

        array_walk($cartItems, [$this, 'removeExtraColumns']);

        return $cartItems;
    }

    private function getCollectorCartAsArray(
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        $checkoutItems = $checkoutData->getCart()->getItems();

        array_walk($checkoutItems, [$this, 'toArrayOnElements']);
        array_walk($checkoutItems, [$this, 'removeExtraColumns']);

        return $checkoutItems;
    }

    private function getCollectorFeesAsArray(
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        $checkoutItems = $checkoutData->getFees()->toArray();

        array_walk($checkoutItems, [$this, 'removeExtraColumns']);

        return $checkoutItems;
    }

    private function calculateCollectorTotal(
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        $cartTotal = $this->calculateCollectorCartTotal($checkoutData);
        $feesTotal = $this->calculateCollectorFeesTotal($checkoutData);

        return $cartTotal + $feesTotal;
    }

    private function calculateCollectorCartTotal(
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        $cartItems = $this->getCollectorCartAsArray($checkoutData);

        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['unitPrice'] * $item['quantity'];
        }

        return $total;
    }

    private function calculateCollectorFeesTotal(
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) {
        $cartItems = $this->getCollectorFeesAsArray($checkoutData);

        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['unitPrice'];
        }

        return $total;
    }

    private function serializeElements(&$item, $key)
    {
        $item = serialize($item);
    }

    private function removeExtraColumns(&$item, $key)
    {
        unset($item['requiresElectronicId'], $item['sku'], $item['description']);
    }

    private function toArrayOnElements(&$item, $key)
    {
        $item = $item->toArray();
    }
}
