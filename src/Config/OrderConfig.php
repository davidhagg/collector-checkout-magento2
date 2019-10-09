<?php

namespace Webbhuset\CollectorBankCheckout\Config;


class OrderConfig extends \Webbhuset\CollectorBankCheckout\Config\Config
{
    protected $orderDataHandler;
    protected $order;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webbhuset\CollectorBankCheckout\Config\Source\Country\Country $countryData,
        \Webbhuset\CollectorBankCheckout\Data\OrderHandler $orderDataHandler,
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        $this->orderDataHandler = $orderDataHandler;
        $this->order = $order;

        parent::__construct($scopeConfig, $encryptor, $storeManager, $countryData);
    }

    protected function getOrder() : \Magento\Sales\Api\Data\OrderInterface
    {
        return $this->order;
    }

    public function getStoreId() : string
    {
        $storeId = $this->orderDataHandler->getStoreId($this->getOrder());

        if ($storeId) {
            return $storeId;
        }

        return parent::getStoreId();
    }
}
