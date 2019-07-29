<?php

namespace Webbhuset\CollectorBankCheckout\Config\Source\Customer;

class Type implements \Magento\Framework\Data\OptionSourceInterface
{
    const PRIVATE_CUSTOMERS = 1;
    const BUSINESS_CUSTOMERS = 2;
    const BOTH_CUSTOMERS = 3;

    public function toOptionArray()
    {
        return [
            self::PRIVATE_CUSTOMERS => __('Private customers'),
            self::BUSINESS_CUSTOMERS => __('Business customers'),
            self::BOTH_CUSTOMERS => __('Both')
        ];
    }
}