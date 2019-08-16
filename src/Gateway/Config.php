<?php

namespace Webbhuset\CollectorBankCheckout\Gateway;

use Magento\Checkout\Model\ConfigProviderInterface;

class Config implements ConfigProviderInterface
{
    const CHECKOUT_CODE = "collectorbank_checkout";
    const PAYMENT_METHOD_NAME = "Collector Bank Checkout";

    public function getConfig()
    {
        // TODO: Implement getConfig() method.
    }
}