<?php

namespace Webbhuset\CollectorCheckout;

use Webbhuset\CollectorCheckoutSDK\Checkout\Cart;
use Webbhuset\CollectorCheckoutSDK\Checkout\Cart\Item;
use Webbhuset\CollectorCheckoutSDK\Checkout\Customer\InitializeCustomer;
use Webbhuset\CollectorCheckoutSDK\Checkout\Fees;
use Webbhuset\CollectorCheckoutSDK\Checkout\Fees\Fee;

class QuoteConverter
{
    protected $taxConfig;
    protected $taxCalculator;
    protected $scopeConfig;
    protected $configurationHelper;

    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $taxCalculator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Helper\Product\Configuration $configurationHelper
    ) {
        $this->taxConfig            = $taxConfig;
        $this->taxCalculator        = $taxCalculator;
        $this->scopeConfig          = $scopeConfig;
        $this->configurationHelper  = $configurationHelper;
    }

    public function getCart(\Magento\Quote\Model\Quote $quote) : Cart
    {
        $quoteItems = $quote->getAllVisibleItems();
        $items = [];

        foreach ($quoteItems as $quoteItem) {

            if (\Magento\Bundle\Model\Product\Type::TYPE_CODE === $quoteItem->getProductType()) {
                $items = array_merge($items, $this->extractBundleQuoteItem($quoteItem));
            } else {
                $items = array_merge($items, $this->extractQuoteItem($quoteItem));
            }
        }

        $roundingError = $this->addRoundingError($quote, $items);
        if ($roundingError) {
            $items = array_merge($items, [$roundingError]);
        }

        $cart = new Cart($items);

        return $cart;
    }

    protected function extractQuoteItem($quoteItem)
    {
        $items[] = $this->getCartItem($quoteItem);

        if ((float)$quoteItem->getDiscountAmount()) {
            $items[] = $this->getDiscountItem($quoteItem);
        }

        return $items;
    }

    protected function extractBundleQuoteItem($quoteItem)
    {
        $items = [];

        $childrenTotal = 0;
        foreach ($quoteItem->getChildren() as $child) {
            $childrenItem = $this->getCartItem($child, "- ");
            $items[] = $childrenItem;
            $childrenTotal += $childrenItem->getUnitPrice();
            if ((float)$child->getDiscountAmount()) {
                $items[] = $this->getDiscountItem($child,'- ' . __('Discount: '));
            }
        }
        $bundleParent = [];
        $bundleParent[] = ($childrenTotal > 0) ?
            $this->getCartItem($quoteItem, "",true):
            $this->getCartItem($quoteItem);

        $items = $this->appendToItems($bundleParent, $items);
        if ((float)$quoteItem->getDiscountAmount()) {
            $items[] = $this->getDiscountItem($quoteItem, __('Discount: '));
        }

        return $items;
    }

    protected function addRoundingError(\Magento\Quote\Model\Quote $quote, $items)
    {
        $collectorCheckoutSum = $this->sumItems($items) + $this->sumFees($this->getFees($quote));
        $quoteSum = $quote->getGrandTotal();

        $roundingError = round($quoteSum - $collectorCheckoutSum,2);
        if (!($roundingError != 0 && abs($roundingError) < 0.1)) {

            return false;
        }

        return new Item(
            "Rounding",
            "Rounding",
            $roundingError,
            1,
            0,
            false,
            "Rounding"
        );

    }

    public function getCartItem(\Magento\Quote\Model\Quote\Item $quoteItem, $prefix = "",  $priceIsZero = false) : Item
    {
        $optionText = $this->getSelectedOptionText($quoteItem);

        $id                     = (string) $prefix . $quoteItem->getSku();
        $description            = (string) $quoteItem->getName() . $optionText;
        $unitPrice              = ($priceIsZero) ? 0.00: (float) $quoteItem->getPriceInclTax();
        $quantity               = (int) $quoteItem->getQty();
        $vat                    = (float) $quoteItem->getTaxPercent();
        $requiresElectronicId   = (bool) $this->requiresElectronicId($quoteItem);
        $sku                    = (string) $quoteItem->getSku() . $optionText;

        $item = new Item(
            $id,
            $description,
            round($unitPrice, 2),
            $quantity,
            $vat,
            $requiresElectronicId,
            $sku
        );

        return $item;
    }

    public function getDiscountItem(\Magento\Quote\Model\Quote\Item $quoteItem, $prefix = "")
    {
        $discountAmount = $quoteItem->getDiscountAmount();
        $taxPercent = $quoteItem->getTaxPercent();
        $priceIncludesTax = $this->scopeConfig->getValue(
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $discountTax = 0;
        if ($taxPercent && !$priceIncludesTax) {
            $discountTax = ($discountAmount * $taxPercent / 100);
        }

        $id                     = (string) $prefix . $quoteItem->getSku();
        $description            = (string) __('collector_discount');
        $unitPrice              = (float) ($discountAmount + $discountTax) * -1;
        $quantity               = (int) 1;
        $vat                    = (float) 0;

        $item = new Item(
            $id,
            $description,
            round($unitPrice, 2),
            $quantity,
            $vat
        );

        return $item;
    }

    public function getFees(\Magento\Quote\Model\Quote $quote) : Fees
    {
        $shippingFee        = $this->getShippingFee($quote);
        $directInvoiceFee   = $this->getDirectInvoiceFee($quote);

        $fees = new Fees(
            $shippingFee,
            $directInvoiceFee
        );

        return $fees;
    }

    public function getShippingFee(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $method = $shippingAddress->getShippingMethod();
        if (!$method) {
            return null;
        }

        $id          = (string) $method;
        $description = ((string) $shippingAddress->getShippingDescription()) ? ((string) $shippingAddress->getShippingDescription()) : (string) $method;
        $unitPrice   = (float) $shippingAddress->getShippingInclTax();
        $vatPercent  = (float) $this->getShippingTaxPercent($quote);

        $fee = new Fee(
            $id,
            $description,
            $unitPrice,
            $vatPercent
        );

        return $fee;
    }

    public function getShippingTaxPercent(\Magento\Quote\Model\Quote $quote) : float
    {
        $request = $this->taxCalculator->getRateRequest(
            $quote->getShippingAddress(),
            $quote->getBillingAddress(),
            $quote->getCustomerTaxClassId(),
            $quote->getStoreId()
        );

        $shippingTaxClassId = $this->taxConfig->getShippingTaxClass($quote->getStoreId());
        $vatPercent = (float) $this->taxCalculator->getRate($request->setProductClassId($shippingTaxClassId));

        return $vatPercent;
    }

    public function getDirectInvoiceFee(\Magento\Quote\Model\Quote $quote)
    {
        return null;
    }

    public function getInitializeCustomer(\Magento\Quote\Model\Quote $quote)
    {
        $email                          = (string) $this->getEmail($quote);
        $mobilePhoneNumber              = (string) $this->getMobilePhoneNumber($quote);
        $nationalIdentificationNumber   = (string) $this->getNationalIdentificationNumber($quote);
        $postalCode                     = (string) $this->getPostalCode($quote);

        // Email and mobile phone number are required. If we don't have both, we return null
        if ($email && $mobilePhoneNumber) {
            $customer = new InitializeCustomer(
                $email,
                $mobilePhoneNumber,
                $nationalIdentificationNumber,
                $postalCode
            );

            return $customer;
        }

        return null;
    }

    public function getEmail(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $email = $quote->getCustomerEmail() ?? $shippingAddress->getEmail();

        return $email;
    }

    public function getMobilePhoneNumber(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();

        return $shippingAddress->getTelephone();
    }

    public function getNationalIdentificationNumber(\Magento\Quote\Model\Quote $quote)
    {
        return null;
    }

    public function getPostalCode(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();

        return $shippingAddress->getPostcode();
    }

    public function getReference(\Magento\Quote\Model\Quote $quote)
    {
        return $quote->getReservedOrderId();
    }

    public function requiresElectronicId($quoteItem)
    {
        if ($quoteItem->getIsVirtual()) {
            return true;
        }

        if ($quoteItem->getProductType() == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE) {
            return true;
        }

        return false;
    }

    private function sumFees($fees)
    {
        $sum = 0;
        $fees = ($fees->toArray());
        foreach ($fees as $fee) {
            $sum += $fee['unitPrice'];
        }

        return $sum;
    }

    private function sumItems($items)
    {
        $sum = 0;
        foreach ($items as $item) {
            $sum += $item->getUnitPrice() * $item->getQuantity();
        }

        return $sum;
    }

    private function getSelectedOptionText(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item)
    {
        $optionTexts = $this->getSelectedOptionsOfQuoteItem($item);

        $result = [];
        foreach ($optionTexts as $option) {
            $result[] = $option['value'];
        }

        if (empty($result)) {

            return "";
        }

        return ":" . implode("-",$result);
    }

    private function getSelectedOptionsOfQuoteItem(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item)
    {
        return $this->configurationHelper->getCustomOptions($item);
    }
}
