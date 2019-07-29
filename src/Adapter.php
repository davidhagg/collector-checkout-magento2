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

    protected function getConfig($storeId = null) : \CollectorBank\CheckoutSDK\Config\ConfigInterface
    {
        return $this->config;
    }

    protected function getAdapter($config)
    {
        // Todo take this module config object


        // if ($config->isMockMode()) {
            // return new \CollectorBank\CheckoutSDK\Adapter\MockAdapter($config);
        // }


        return new \CollectorBank\CheckoutSDK\Adapter\CurlAdapter($config);
    }
}
