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

    public function initialize(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
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

    public function acquireCheckoutInformation(\Magento\Quote\Model\Quote $quote): \CollectorBank\CheckoutSDK\CheckoutData
    {
        $privateId = 'b4bcb34e-91ad-4942-931d-d8d8a52453e5';
        $config = $this->getConfig();
        $adapter = $this->getAdapter($config);
        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);
        $collectorSession->load($privateId);

        return $collectorSession->getCheckoutData();
    }

    public function updateFees(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $config = $this->getConfig($quote->getStoreId());
        $adapter = $this->getAdapter($config);
        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);

        $fee = $this->quoteConverter->getFees($quote);
        $privateId = '123';

        try {
            $collectorSession->setPrivateId($privateId)
                ->updateFees($fees);

        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {
            die;
        }

        return $collectorSession;
    }

    public function updateCart(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $config = $this->getConfig($quote->getStoreId());
        $adapter = $this->getAdapter($config);
        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);

        $cart = $this->quoteConverter->getCart($quote);
        $privateId = '123';

        try {
            $collectorSession->setPrivateId($privateId)
                ->updateCart($cart);

        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {
            die;
        }

        return $collectorSession;
    }

    public function getConfig($storeId = null) : \CollectorBank\CheckoutSDK\Config\ConfigInterface
    {
        return $this->config;
    }

    public function getAdapter($config) : \CollectorBank\CheckoutSDK\Adapter\AdapterInterface
    {
        $config = $this->getConfig();

        if ($config->getIsMockMode()) {
            return new \CollectorBank\CheckoutSDK\Adapter\MockAdapter($config);
        }

        return new \CollectorBank\CheckoutSDK\Adapter\CurlAdapter($config);
    }
}
