<?php

namespace Webbhuset\CollectorBankCheckout\Block\Admin;

class OrderInfo extends \Magento\Payment\Block\Info
{
    protected $_template = 'Webbhuset_CollectorBankCheckout::info/checkout.phtml';

    public function getPaymentInfo()
    {
        return $this->getInfo()->getAdditionalInformation();
    }
}