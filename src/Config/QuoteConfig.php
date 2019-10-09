<?php

namespace Webbhuset\CollectorCheckout\Config;

use Magento\Quote\Api\Data\CartInterface as Quote;

class QuoteConfig extends \Webbhuset\CollectorCheckout\Config\Config
{
    protected $quoteDataHandler;
    protected $quote;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webbhuset\CollectorCheckout\Config\Source\Country\Country $countryData,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler,
        Quote $quote
    ) {
        $this->quoteDataHandler = $quoteDataHandler;
        $this->quote = $quote;

        parent::__construct($scopeConfig, $encryptor, $storeManager, $countryData);
    }

    protected function getQuote() : Quote
    {
        return $this->quote;
    }

    public function getStoreId() : string
    {
        $storeId = $this->quoteDataHandler->getStoreId($this->getQuote());

        if ($storeId) {
            return $storeId;
        }

        return parent::getStoreId();
    }
}
