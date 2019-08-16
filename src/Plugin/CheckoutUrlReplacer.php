<?php

namespace Webbhuset\CollectorBankCheckout\Plugin;

use Webbhuset\CollectorBankCheckout\Gateway\Config;

class CheckoutUrlReplacer
{
    protected $config;

    public function __construct(\Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config)
    {
        $this->config = $config;
    }

    public function beforeGetUrl(
        \Magento\Framework\UrlInterface $urlInterface,
        $param1 = null,
        $params2 = null
    ) {
        $config = $this->config->create();
        if ($config->getIsActive()) {
            $param1 = ('checkout' == $param1) ? Config::CHECKOUT_URL_KEY : $param1;
        }

        return [$param1, $params2];
    }
}
