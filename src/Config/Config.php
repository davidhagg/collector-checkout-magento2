<?php

namespace Webbhuset\CollectorBankCheckout\Config;

use Webbhuset\CollectorBankCheckout\Config\Source\Customer\Type as AllowedCustomerType;

class Config implements
    \CollectorBank\CheckoutSDK\Config\ConfigInterface,
    \CollectorBank\PaymentSDK\Config\ConfigInterface
{
    protected $scopeConfig;
    protected $storeManager;
    protected $encryptor;
    protected $checkoutSession;
    protected $quoteDataHandler;
    protected $orderDataHandler;
    protected $storeId;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorBankCheckout\Data\OrderHandler $orderDataHandler
    ) {
        $this->scopeConfig      = $scopeConfig;
        $this->encryptor        = $encryptor;
        $this->checkoutSession  = $checkoutSession;
        $this->storeManager     = $storeManager;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->orderDataHandler = $orderDataHandler;
    }

    public function getIsActive(): bool
    {
        return 1 == $this->getConfigValue('active');
    }

    public function getCreateCustomerAccount(): bool
    {
        return 1 == $this->getConfigValue('create_customer_account');
    }

    public function getConfig(): array
    {
        $data = [
            'is_active'               => $this->getIsActive(),
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
            'productionModeB2B'       => $this->getProductionModeB2B(),
            'customBaseUrl'           => $this->getCustomBaseUrl(),
            'createCustomerAccount'   => $this->getCreateCustomerAccount(),
            'styleDataLang'           => $this->getStyleDataLang(),
            'styleDataPadding'        => $this->getStyleDataPadding(),
            'styleDataContainerId'    => $this->getStyleDataContainerId(),
            'styleDataActionColor'    => $this->getStyleDataActionColor(),
            'styleDataActionTextColor'=> $this->getStyleDataActionTextColor()
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
        return $this->getConfigValue('country_code');
    }

    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    public function getStoreId() : string
    {
        if ($this->storeId) {
            return $this->storeId;
        }

        return $this->getCustomerStoreId();
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
        return $this->getConfigValue('customer_type') ? $this->getConfigValue('customer_type') : 0;
    }

    public function getDefaultCustomerType(): int
    {
        return $this->getConfigValue('default_customer_type') ? $this->getConfigValue('default_customer_type') : 0;
    }

    public function getIsMockMode(): bool
    {
        return false;
    }

    public function getIsTestMode(): bool
    {
        return $this->getConfigValue('test_mode') ? $this->getConfigValue('test_mode') : false;
    }

    public function getMerchantTermsUri(): string
    {
        return $this->getConfigValue('terms_url') ? $this->getConfigValue('terms_url') : "";
    }

    public function getRedirectPageUri(): string
    {
        $checkoutUrl = \Webbhuset\CollectorBankCheckout\Gateway\Config::CHECKOUT_URL_KEY;
        $urlKey = $checkoutUrl . "/success/index/reference/{checkout.publictoken}";

        $url = $this->storeManager->getStore()->getUrl($urlKey);

        return $url;
    }

    public function getNotificationUri() : string
    {
        $urlKey = "collectorbank/notification/index/reference/{checkout.publictoken}";

        if ($this->getCustomBaseUrl()) {
            return $this->getCustomBaseUrl() . $urlKey;
        }

        return $this->storeManager->getStore()->getUrl($urlKey);
    }

    public function getValidationUri(): string
    {
        $urlKey = "collectorbank/validation/index/reference/{checkout.publictoken}";

        if ($this->getCustomBaseUrl()) {
            return $this->getCustomBaseUrl() . $urlKey;
        }

        return $this->storeManager->getStore()->getUrl($urlKey);
    }

    public function getOrderStatusNew(): string
    {
        return $this->getConfigValue('order_status');
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
        $profileName = $this->getConfigValue('profile_name');
        return $profileName ? $profileName : "";
    }

    public function getProductionModeUsername(): string
    {
        return $this->getConfigValue('username') ? $this->getConfigValue('username') : "";
    }

    public function getProductionModePassword(): string
    {
        $value = $this->getConfigValue('password');
        if (!$value) {
            return "";
        }

        $value = $this->encryptor->decrypt($value);

        return $value;
    }

    public function getProductionModeB2C() : string
    {
        return $this->getConfigValue('b2c') ? $this->getConfigValue('b2c') : "";
    }

    public function getProductionModeB2B() : string
    {
        return $this->getConfigValue('b2b') ? $this->getConfigValue('b2b') : "";
    }

    public function getTestModeUsername(): string
    {
        return $this->getConfigValue('test_mode_username') ? $this->getConfigValue('test_mode_username') : "";
    }

    public function getTestModePassword(): string
    {
        $value = $this->getConfigValue('test_mode_password');
        if (!$value) {

            return "";
        }
        $value = $this->encryptor->decrypt($value);

        return $value;
    }

    public function getTestModeB2C(): string
    {
        return $this->getConfigValue('test_mode_b2c') ? $this->getConfigValue('test_mode_b2c') : "";
    }

    public function getTestModeB2B(): string
    {
        return $this->getConfigValue('test_mode_b2b') ? $this->getConfigValue('test_mode_b2b') : "";
    }

    protected function getConfigValue($name)
    {
        $value = $this->scopeConfig->getValue(
            'payment/collectorbank_checkout/configuration/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $value;
    }

    public function getMode()
    {
        $mode = $this->getIsTestMode() ? "test mode" : "production mode";

        return $this->getIsMockMode() ? "mock mode" : $mode;
    }

    public function isTestMode(): bool
    {
        return $this->getIsTestMode();
    }

    public function isProductionMode(): bool
    {
        return !$this->getIsTestMode();
    }

    public function getCustomBaseUrl()
    {
        return $this->getConfigValue('custom_base_url');
    }

    public function getCustomerType()
    {
        $quote = $this->checkoutSession->getQuote();
        $customerType = $this->quoteDataHandler->getCustomerType($quote);

        if ($customerType) {
            return $customerType;
        }

        return $this->getDefaultCustomerType();
    }

    public function getCustomerStoreId()
    {
        $customerType = $this->getCustomerType();

        if (\Webbhuset\CollectorBankCheckout\Config\Source\Customer\DefaultType::PRIVATE_CUSTOMERS == $customerType) {
            return $this->getB2C();
        } else {
            return $this->getB2B();
        }
    }

    public function getStoreIdForOrder(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $customerType = $this->orderDataHandler->getStoreId($order);
        if (AllowedCustomerType::PRIVATE_CUSTOMERS == $customerType) {
            return $this->getB2C();
        }

        return $this->getB2B();
    }

    public function getCheckoutUrl()
    {
        $urlKey = \Webbhuset\CollectorBankCheckout\Gateway\Config::CHECKOUT_URL_KEY;
        $url = $this->storeManager->getStore()->getUrl($urlKey);

        return $url;
    }

    public function getStyleDataLang()
    {
        $data = $this->getConfigValue('style_data_lang');

        return ($data) ? $data : null;
    }

    public function getStyleDataPadding()
    {
        $data = $this->getConfigValue('style_data_padding');

        return ($data) ? $data : null;
    }

    public function getStyleDataContainerId()
    {
        $data = $this->getConfigValue('style_data_container_id');

        return ($data) ? $data : null;
    }

    public function getStyleDataActionColor()
    {
        $data = $this->getConfigValue('style_data_action_color');

        return ($data) ? $data : null;
    }

    public function getStyleDataActionTextColor()
    {
        $data = $this->getConfigValue('style_data_action_text_color');

        return ($data) ? $data : null;
    }
}
