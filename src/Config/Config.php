<?php

namespace Webbhuset\CollectorBankCheckout\Config;

class Config implements \CollectorBank\CheckoutSDK\Config\ConfigInterface
{
    protected $scopeConfig;
    protected $storeManager;
    protected $encryptor;
    protected $checkoutSession;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig     = $scopeConfig;
        $this->encryptor       = $encryptor;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager    = $storeManager;
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
            'orderStatusNew'          => $this->getOrderStatusNew(),
            'orderStatusAcknowledged' => $this->getOrderStatusAcknowledged(),
            'orderStatusHolded'       => $this->getOrderStatusHolded(),
            'orderStatusDenied'       => $this->getOrderStatusDenied(),
            'profileName'             => $this->getProfileName(),
            'testModeUsername'        => $this->getTestModeUsername(),
            'testModePassword'        => $this->getTestModePassword(),
            'testModeB2C'             => $this->getTestModeB2C(),
            'testModeB2B'             => $this->getTestModeB2B(),
            'productionModeUsername'  => $this->getProductionModeUsername(),
            'productionModePassword'  => $this->getProductionModePassword(),
            'productionModeB2C'       => $this->getProductionModeB2C(),
            'productionModeB2B'       => $this->getProductionModeB2B()
        ];

        return $data;
    }

    public function getUsername() : string
    {
        return $this->getIsTestMode() ? $this->getTestModeUsername() : $this->getProductionModeUsername();
    }

    public function getSharedAccessKey() : string
    {
        return $this->getPassword();
    }

    public function getPassword() : string
    {
        return $this->getIsTestMode() ? $this->getTestModePassword() : $this->getProductionModePassword();
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
        return $this->getIsTestMode() ? $this->getTestModeB2C() : $this->getProductionModeB2C();
    }

    public function getB2B() : string
    {
        return $this->getIsTestMode() ? $this->getTestModeB2B() : $this->getProductionModeB2B();
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
        $quoteId = $this->checkoutSession->getQuoteId();
        $urlKey = "collectorbank/validation/index/quoteid/$quoteId";

        return $this->storeManager->getStore()->getUrl($urlKey);
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

    public function getProductionModeUsername(): string
    {
        return $this->getConfigValue('username');
    }

    public function getProductionModePassword(): string
    {
        $value = $this->getConfigValue('password');
        $value = $this->encryptor->decrypt($value);

        return $this->getConfigValue($value);
    }

    public function getProductionModeB2C() : string
    {
        return $this->getConfigValue('b2c');
    }

    public function getProductionModeB2B() : string
    {
        return $this->getConfigValue('b2b');
    }

    public function getTestModeUsername(): string
    {
        return $this->getConfigValue('test_mode_username');
    }

    public function getTestModePassword(): string
    {
        $value = $this->getConfigValue('test_mode_password');
        $value = $this->encryptor->decrypt($value);

        return $this->getConfigValue($value);
    }

    public function getTestModeB2C(): string
    {
        return $this->getConfigValue('test_mode_b2c');
    }

    public function getTestModeB2B(): string
    {
        return $this->getConfigValue('test_mode_b2b');
    }

    private function getConfigValue($name)
    {
        $value = $this->scopeConfig->getValue('payment/webbhuset_collectorbankcheckout/' . $name);

        return $value;
    }
}
