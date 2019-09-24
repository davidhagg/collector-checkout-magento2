<?php

namespace Webbhuset\CollectorBankCheckout;

use CollectorBank\CheckoutSDK\Checkout\Customer as SDK;
use Magento\Quote\Model\Quote as Quote;

class QuoteUpdater
{
    protected $taxConfig;
    protected $taxCalculator;
    protected $shippingMethodManagement;
    protected $config;
    protected $session;
    protected $customerRepositoryInterface;

    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $taxCalculator,
        \Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Model\Session $session,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManagement
    ) {
        $this->taxConfig                   = $taxConfig;
        $this->config                      = $config;
        $this->taxCalculator               = $taxCalculator;
        $this->shippingMethodManagement    = $shippingMethodManagement;
        $this->session                     = $session;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    public function setQuoteData(
        Quote $quote,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) : Quote {
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

        $quote->setDefaultShippingAddress($shippingAddress);
        $quote->setDefaultBillingAddress($billingAddress);

        $shippingAddress->setCollectShippingRates(true);
        $quote->setNeedsCollectorUpdate(true);

        $this->setCustomerData($quote, $checkoutData);
        $this->setPaymentMethod($quote);

        $customerLoggedIn = $this->session->isLoggedIn();
        if (!$customerLoggedIn) {
            $quote->setCustomerIsGuest(true);
        } else {
            $customerId = $this->session->getCustomer()->getId();
            $customer = $this->customerRepositoryInterface->getById($customerId);

            $this->customerRepositoryInterface->save($customer);

            $quote->setCustomer($customer);
        }

        return $quote;
    }

    public function setDefaultShippingIfEmpty(
        Quote $quote
    ) : Quote {
        if ($quote->getShippingAddress()->getShippingMethod()) {
            return $quote;
        }
        $shippingAddress = $quote->getShippingAddress();
        $countryCode = $this->config->create()->getCountryCode();

        $shippingAddress->setCountryId($countryCode)
            ->setCollectShippingRates(true)
            ->collectShippingRates();

        $this->setDefaultShippingMethod($quote);

        return $quote;
    }

    public function setDefaultShippingMethod($quote)
    {
        $defaultShippingMethod = $this->getDefaultShippingMethod($quote);

        if ($defaultShippingMethod) {
            $quote->getShippingAddress()
                ->setShippingMethod($defaultShippingMethod);
        }
    }

    protected function getDefaultShippingMethod(Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $rates = $this->shippingMethodManagement->getList($quote->getId());

        if (empty($rates)) {
            return false;
        }

        $shippingMethod = reset($rates);
        foreach ($rates as $rate) {
            $method = $rate->getCarrierCode() . '_' . $rate->getMethodCode();
            if ($method === $shippingAddress->getShippingMethod()) {
                $shippingMethod = $rate;
                break;
            }
        }

        return $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode();
    }

    public function setCustomerData(
        Quote $quote,
        \CollectorBank\CheckoutSDK\CheckoutData $checkoutData
    ) : Quote {
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
    ) : Quote {
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
            ->setCompany($collectorAddress->getCompanyName())
            ->setStreet([
                $collectorAddress->getCoAddress(),
                $collectorAddress->getAddress(),
                $collectorAddress->getAddress2()
            ])->setPostCode($collectorAddress->getPostalCode())
            ->setCity($collectorAddress->getCity());

        return $address;
    }
}
