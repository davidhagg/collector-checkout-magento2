<?php

namespace Webbhuset\CollectorBankCheckout;

use Magento\Quote\Model\Quote;
use CollectorBank\CheckoutSDK\Checkout\Customer as SDK;

class QuoteUpdater
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

    public function setQuoteData(
        Quote $quote,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) : Quote
    {

        $customer                   = $checkoutData->getCustomer();
        $collectorInvoiceAddress    = $customer->getInvoiceAddress();
        $billingAddress             = $quote->getBillingAddress();
        $collectorDeliveryAddress   = $customer->getDeliveryAddress();
        $shippingAddress            = $quote->getShippingAddress();

        if ($customer instanceof SDK\PrivateCustomer) {
            $billingAddress = $this->setPrivateAddressData($billingAddress, $customer, $collectorInvoiceAddress)
                ->setCountryId($checkoutData->getCountryCode());
            $shippingAddress = $this->setPrivateAddressData($shippingAddress, $customer, $collectorDeliveryAddress)
                ->setCountryId($checkoutData->getCountryCode());
        }

        if ($customer instanceof SDK\BusinessCustomer) {
            $billingAddress = $this->setBusinessAddressData($billingAddress, $customer, $collectorInvoiceAddress)
                ->setCountryId($checkoutData->getCountryCode());
            $shippingAddress = $this->setBusinessAddressData($shippingAddress, $customer, $collectorDeliveryAddress)
                ->setCountryId($checkoutData->getCountryCode());
        }

        $this->setCustomerData($quote, $checkoutData);
        $this->setPaymentMethod($quote);
        $quote->setCustomerIsGuest(true);

        return $quote;
    }

    public function setCustomerData(
        Quote $quote,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) : Quote
    {
        $customer = $checkoutData->getCustomer();
        $customerAddress = $customer->getInvoiceAddress();

        $firstname = $customerAddress->getFirstName();
        $lastname  = $customerAddress->getLastName();
        $email = $customer->getEmail();

        $quote->setCustomerFirstname($firstname)
            ->setCustomerLastname($lastname)
            ->setCustomerEmail($email);

        return $quote;
    }

    public function setPaymentMethod(
        Quote $quote
    ) : Quote
    {
        $payment = $quote->getPayment();
        $payment->setMethod(\Webbhuset\CollectorBankCheckout\Gateway\Config::CHECKOUT_CODE);

        return $quote;
    }

    public function setPrivateAddressData(
        Quote\Address $address,
        SDK\PrivateCustomer $customer,
        SDK\PrivateAddress $collectorAddress
    ) {
        $address->setEmail($customer->getEmail())
            ->setTelephone($customer->getMobilePhoneNumber())
            ->setFirstname($collectorAddress->getFirstName())
            ->setLastname($collectorAddress->getLastName())
            ->setStreet([
                $collectorAddress->getCoAddress(),
                $collectorAddress->getAddress(),
                $collectorAddress->getAddress2()
            ])->setPostCode($collectorAddress->getPostalCode())
            ->setCity($collectorAddress->getCity());

        return $address;
    }

    public function setBusinessAddressData(
        Quote\Address $address,
        SDK\BusinessCustomer $customer,
        SDK\BusinessAddress $collectorAddress
    ) {
        $address->setEmail($customer->getEmail())
            ->setTelephone($customer->getMobilePhoneNumber())
            ->setFirstname($customer->getFirstName())
            ->setLastname($customer->getLastName())
            ->setCompany($collectorAddress->getCompanyName)
            ->setStreet([
                $collectorAddress->getCoAddress(),
                $collectorAddress->getAddress(),
                $collectorAddress->getAddress2()
            ])->setPostCode($collectorAddress->getPostalCode())
            ->setCity($collectorAddress->getCity());

        return $address;
    }
}
