<?php

namespace Webbhuset\CollectorBankCheckout\Gateway;

use Magento\Checkout\Model\ConfigProviderInterface;

class Config implements ConfigProviderInterface
{
    const CHECKOUT_CODE = "collectorbank_checkout";

    public function getConfig()
    {
        // TODO: Implement getConfig() method.
    }
}