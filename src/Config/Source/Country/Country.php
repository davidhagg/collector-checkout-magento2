<?php

namespace Webbhuset\CollectorBankCheckout\Config\Source\Country;

class Country implements \Magento\Framework\Data\OptionSourceInterface
{
    const SWEDEN = "SE";
    const NORWAY = "NO";
    const FINLAND = "FI";

    public function toOptionArray()
    {
        return [
            self::SWEDEN => __('Sweden'),
            self::NORWAY => __('Norway'),
            self::FINLAND => __('Finland')
        ];
    }
}