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
        $session = $this->getCollectorSession();

        if (!$session) {
            return '';
        }

        $publicToken = $session->getPublicToken();

        $iframeConfig = new \CollectorBank\CheckoutSDK\Config\IframeConfig(
            $publicToken
        );

        return $session->getIframe($iframeConfig);
    }
}
