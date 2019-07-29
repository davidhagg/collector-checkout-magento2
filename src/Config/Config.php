<?php

namespace Webbhuset\CollectorBankCheckout\Config;

class Config implements \CollectorBank\CheckoutSDK\Config\ConfigInterface
{
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getUsername() : string
    {
        return '';
    }

    public function getSharedAccessKey() : string
    {
        return '';
    }

    public function getCountryCode() : string
    {
        return 'SE';
    }

    public function getStoreId() : string
    {
        return '';
    }


    public function getIsMockMode() : bool
    {
        return true;
    }

    public function getIsTestMode() : bool
    {
        return true;
    }


    public function getMerchantTermsUri() : string
    {
        return '';
    }

    public function getRedirectPageUri()
    {

    }

    public function getNotificationUri() : string
    {
        return '';
    }

    public function getValidationUri()
    {

    }

    public function getProfileName()
    {

    }
}
