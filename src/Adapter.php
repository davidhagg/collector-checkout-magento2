<?php

namespace Webbhuset\CollectorBankCheckout;

class Adapter
{
    protected $quoteConverter;
    protected $config;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorBankCheckout\Config\Config $config
    ) {
        $this->quoteConverter = $quoteConverter;
        $this->config = $config;
    }


    public function initialize(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress =$quote->getShippingAddress()->collectShippingRates();
        $cart = $this->quoteConverter->getCart($quote);
        $fees = $this->quoteConverter->getFees($quote);
        $initCustomer = $this->quoteConverter->getInitializeCustomer($quote);

        $config = $this->getConfig($quote->getStoreId());
        $countryCode = $config->getCountryCode();
        $adapter = $this->getAdapter($config);
        $reference = '';

        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);

        try {
            $collectorSession->initialize(
                $config,
                $fees,
                $cart,
                $countryCode,
                $initCustomer
            );
        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {
            die;
        }

        return $collectorSession;
    }

    public function acquireCheckoutInformation($collectorOrderReference): \CollectorBank\CheckoutSDK\CheckoutData
    {
        $config = $this->getConfig();
        $adapter = $this->getAdapter($config);
        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);
        $collectorSession->load($collectorOrderReference);

        return $collectorSession->getCheckoutData();
    }

    public function getConfig($storeId = null) : \CollectorBank\CheckoutSDK\Config\ConfigInterface
    {
        return $this->config;
    }

    public function getAdapter($config)
    {
        // Todo take this module config object
        $config =  $this->getConfig();


        // if ($config->isMockMode()) {
            return new \CollectorBank\CheckoutSDK\Adapter\MockAdapter($config);
        // }


        return new \CollectorBank\CheckoutSDK\Adapter\CurlAdapter($config);
    }
}
