<?php

namespace Webbhuset\CollectorBankCheckout;

use CollectorBank\CheckoutSDK\Checkout\Fees;
use CollectorBank\CheckoutSDK\Checkout\Fees\Fee;
use CollectorBank\CheckoutSDK\Checkout\Cart;
use CollectorBank\CheckoutSDK\Checkout\Cart\Item;
use CollectorBank\CheckoutSDK\Checkout\Customer\InitializeCustomer;

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
            $items[] = $this->getCartItem($quoteItem);
        }

        $cart = new Cart($items);

        return $cart;
    }

    public function getCartItem(\Magento\Quote\Model\Quote\Item\AbstractItem $quoteItem) : Item
    {
        $id                     = (string) $quoteItem->getSku();
        $description            = (string) $quoteItem->getName();
        $unitPrice              = (float) $quoteItem->getPriceInclTax();
        $quantity               = (int) $quoteItem->getQty();
        $vat                    = (float) $quoteItem->getTaxPercent();
        $requiresElectronicId   = (bool) $quoteItem->getIsVirtual();
        $sku                    = (string) $quoteItem->getSku();

        $item = new Item(
            $id,
            $description,
            $unitPrice,
            $quantity,
            $vat,
            $requiresElectronicId,
            $sku
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

        $id             = (string) $method;
        $description    = (string) $shippingAddress->getShippingDescription();
        $unitPrice      = (float) $shippingAddress->getShippingInclTax();
        $vatPercent     = (float) $this->getShippingTaxPercent($quote);

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
}
