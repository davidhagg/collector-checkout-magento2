<?php

namespace Webbhuset\CollectorBankCheckout\Config\Source\Customer;

class DefaultType implements \Magento\Framework\Data\OptionSourceInterface
{
    const PRIVATE_CUSTOMERS = 1;
    const BUSINESS_CUSTOMERS = 2;

    public function toOptionArray()
    {
        return [
            self::PRIVATE_CUSTOMERS => __('Private customers'),
            self::BUSINESS_CUSTOMERS => __('Business customers'),
        ];
    }
}