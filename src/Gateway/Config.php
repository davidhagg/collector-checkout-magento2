<?php

namespace Webbhuset\CollectorBankCheckout\Gateway;

use Magento\Checkout\Model\ConfigProviderInterface;

class Config implements ConfigProviderInterface
{
    const CHECKOUT_CODE = "collectorbank_checkout";
    const PAYMENT_METHOD_NAME = "Collector Bank Checkout";
    const CHECKOUT_URL_KEY = "collectorcheckout";

    public function getConfig()
    {
        // TODO: Implement getConfig() method.
    }
}