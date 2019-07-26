<?php

namespace Webbhuset\CollectorBankCheckout\Block\Checkout;

class Index extends \Magento\Framework\View\Element\Template
{
    public function toHtml()
    {
        return $this->getIframe();
    }

    public function getIframe()
    {
        $publicToken = 'public-SE-0d9df19a03c0fd74c8afe9407abdbe45aa21df930ae954f3';

        $iframeConfig = new \CollectorBank\CheckoutSDK\Config\IframeConfig(
            $publicToken
        );

        $config = new \CollectorBank\CheckoutSDK\Config\Config;
        $config->setUsername('test')
            ->setSharedAccessKey('test')
            ->setCountryCode('SE')
            ->setStoreId('test')
            ->setRedirectPageUri('url')
            ->setMerchantTermsUri('url')
            ->setNotificationUri('url')
            ->setValidationUri('url');

        $adapter = new \CollectorBank\CheckoutSDK\Adapter\MockAdapter($config);
        $session = new \CollectorBank\CheckoutSDK\Session($adapter);

        $iframe = $session->getIframe($iframeConfig);

        return $iframe;
    }
}
