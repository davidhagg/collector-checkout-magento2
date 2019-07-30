<?php

namespace Webbhuset\CollectorBankCheckout\Config;

class Config implements \CollectorBank\CheckoutSDK\Config\ConfigInterface
{
    protected $scopeConfig;
    protected $storeManager;
    protected $encryptor;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
    }

    public function getConfig(): array
    {
        $data = [
            'username'                => $this->getUsername(),
            'sharedAccessKey'         => $this->getSharedAccessKey(),
            'password'                => $this->getPassword(),
            'countryCode'             => $this->getCountryCode(),
            'storeId'                 => $this->getStoreId(),
            'b2c'                     => $this->getB2C(),
            'b2b'                     => $this->getB2B(),
            'customerTypeAllowed'     => $this->getCustomerTypeAllowed(),
            'defaultCustomerType'     => $this->getDefaultCustomerType(),
            'isMockMode'              => $this->getIsMockMode(),
            'isTestMode'              => $this->getIsTestMode(),
            'merchantTermsUri'        => $this->getMerchantTermsUri(),
            'redirectPageUri'         => $this->getRedirectPageUri(),
            'notificationUri'         => $this->getNotificationUri(),
            'validationUri'           => $this->getValidationUri(),
            'showDiscountBox'         => $this->getShowDiscountBox(),
            'orderStatusNew'          => $this->getOrderStatusNew(),
            'orderStatusAcknowledged' => $this->getOrderStatusAcknowledged(),
            'orderStatusHolded'       => $this->getOrderStatusHolded(),
            'orderStatusDenied'       => $this->getOrderStatusDenied(),
            'profileName'             => $this->getProfileName()
        ];

        return $data;
    }

    public function getUsername() : string
    {
        return $this->getConfigValue('username');
    }

    public function getSharedAccessKey() : string
    {
        $value = $this->getConfigValue('password');
        $value = $this->encryptor->decrypt($value);

        return $value;
    }

    public function getPassword() : string
    {
        return $this->getSharedAccessKey();
    }

    public function getCountryCode() : string
    {
        return 'SE';
    }

    public function getStoreId() : string
    {
        return $this->getB2C();
    }

    public function getB2C() : string
    {
        return $this->getConfigValue('b2c');
    }

    public function getB2B() : string
    {
        return $this->getConfigValue('b2b');
    }

    public function getCustomerTypeAllowed(): int
    {
        return $this->getConfigValue('customer_type');
    }

    public function getDefaultCustomerType(): int
    {
        return $this->getConfigValue('default_customer_type');
    }

    public function getIsMockMode(): bool
    {
        return false;
    }

    public function getIsTestMode(): bool
    {
        return $this->getConfigValue('test_mode');
    }

    public function getMerchantTermsUri(): string
    {
        return $this->getConfigValue('terms_url');
    }

    public function getRedirectPageUri(): string
    {
        $urlKey = "collectorbank/success";
        return $this->storeManager->getStore()->getUrl($urlKey);
    }

    public function getNotificationUri() : string
    {
        $urlKey = "collectorbank/notification/index";
        return $this->storeManager->getStore()->getUrl($urlKey);
    }

    public function getValidationUri(): string
    {
        $urlKey = "collectorbank/validation/index";
        return $this->storeManager->getStore()->getUrl($urlKey);
    }

    public function getShowDiscountBox(): bool
    {
        return $this->getConfigValue('show_discount_box');
    }

    public function getOrderStatusNew(): string
    {
        return $this->getConfigValue('order_new_status');
    }

    public function getOrderStatusAcknowledged(): string
    {
        return $this->getConfigValue('order_accepted_status');
    }

    public function getOrderStatusHolded(): string
    {
        return $this->getConfigValue('order_holded_status');
    }

    public function getOrderStatusDenied(): string
    {
        return $this->getConfigValue('order_denied_status');
    }

    public function getProfileName(): string
    {
        return "profilename";
    }

    private function getConfigValue($name)
    {
        $value = $this->scopeConfig->getValue('payment/webbhuset_collectorbankcheckout/' . $name);

        return $value;
    }
}
