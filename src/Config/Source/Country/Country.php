<?php

namespace Webbhuset\CollectorBankCheckout\Config\Source\Country;

class Country implements \Magento\Framework\Data\OptionSourceInterface
{
    const SWEDEN  = "SE";
    const NORWAY  = "NO";
    const FINLAND = "FI";
    const DENMARK = "DK";
    const GERMANY = "DE";

    public function toOptionArray()
    {
        return [
            self::SWEDEN  => __('Sweden'),
            self::NORWAY  => __('Norway'),
            self::FINLAND => __('Finland'),
            self::DENMARK => __('Denmark'),
            self::GERMANY => __('Germany')
        ];
    }

    public function getCurrencyPerCountry()
    {
        return [
            self::SWEDEN  => 'SEK',
            self::NORWAY  => 'NOK',
            self::FINLAND => 'EUR',
            self::DENMARK => 'DKK',
            self::GERMANY => 'EUR'
        ];
    }

    public function getDefaultLanguagePerCounty()
    {
        return [
            self::SWEDEN  => 'sv-SE',
            self::NORWAY  => 'nb-NO',
            self::FINLAND => 'fi-FI',
            self::DENMARK => 'da-DK',
            self::GERMANY => 'en-DE'
        ];
    }
}
