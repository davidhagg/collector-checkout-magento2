<?php

namespace Webbhuset\CollectorBankCheckout;

use CollectorBank\CheckoutSDK\Checkout\Cart;
use CollectorBank\CheckoutSDK\Checkout\Cart\Item;
use CollectorBank\CheckoutSDK\Checkout\Customer\InitializeCustomer;
use CollectorBank\CheckoutSDK\Checkout\Fees;
use CollectorBank\CheckoutSDK\Checkout\Fees\Fee;

class QuoteConverter
{
    protected $taxConfig;
    protected $taxCalculator;

    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $taxCalculator
    ) {
        $this->taxConfig = $taxConfig;
        $this->taxCalculator = $taxCalculator;
    }

    public function getCart(\Magento\Quote\Model\Quote $quote) : Cart
    {
        $quoteItems = $quote->getAllVisibleItems();
        $items = [];
        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getProductType() === 'bundle') {
                foreach ($quoteItem->getChildren() as $child) {
                    $items[] = $this->getCartItem($child);

                    if ((float)$child->getDiscountAmount()) {
                        $items[] = $this->getDiscountItem($child);
                    }
                }
            } else {
                $items[] = $this->getCartItem($quoteItem);

                if ((float)$quoteItem->getDiscountAmount()) {
                    $items[] = $this->getDiscountItem($quoteItem);
                }
            }
        }

        $cart = new Cart($items);

        return $cart;
    }

    public function getCartItem(\Magento\Quote\Model\Quote\Item $quoteItem) : Item
    {
        $id                     = (string) $quoteItem->getSku();
        $description            = (string) $quoteItem->getName();
        $unitPrice              = (float) $quoteItem->getPriceInclTax();
        $quantity               = (int) $quoteItem->getQty();
        $vat                    = (float) $quoteItem->getTaxPercent();
        $requiresElectronicId   = (bool) $this->requiresElectronicId($quoteItem);
        $sku                    = (string) $quoteItem->getSku();

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

    public function getDiscountItem(\Magento\Quote\Model\Quote\Item $quoteItem)
    {
        $discountAmount = $quoteItem->getDiscountAmount();
        $taxPercent = $quoteItem->getTaxPercent();
        $discountTax = 0;
        if ($taxPercent) {
            $discountTax = ($discountAmount * $taxPercent / 100);
        }

        $id                     = (string) $quoteItem->getSku();
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
}
