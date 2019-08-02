<?php

namespace Webbhuset\CollectorBankCheckout\Block;

class Checkout extends \Magento\Framework\View\Element\Template
{
    protected $iframe;

    public function getIframe()
    {
        return $this->iframe;
    }

    public function setIframe($iframe)
    {
        $this->iframe = $iframe;

        return $this;
    }

    public function getUpdateUrl()
    {
        return $this->getUrl('collectorcheckout/update');
    }
}
