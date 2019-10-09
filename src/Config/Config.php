<?php

namespace Webbhuset\CollectorBankCheckout\Config;

use Webbhuset\CollectorBankCheckout\Config\Source\Customer\Type as AllowedCustomerType;

/**
 * Class Config
 *
 * @package Webbhuset\CollectorBankCheckout\Config
 */
class Config implements
    \CollectorBank\CheckoutSDK\Config\ConfigInterface,
    \CollectorBank\PaymentSDK\Config\ConfigInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Data\QuoteHandler
     */
    protected $quoteDataHandler;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Data\OrderHandler
     */
    protected $orderDataHandler;
    /**
     * @var int $storeId
     */
    protected $storeId;
    /**
     * @var Source\Country\Country
     */
    protected $countryData;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Encryption\EncryptorInterface   $encryptor
     * @param \Magento\Checkout\Model\Session                    $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler
     * @param \Webbhuset\CollectorBankCheckout\Data\OrderHandler $orderDataHandler
     * @param Source\Country\Country                             $countryData
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorBankCheckout\Data\OrderHandler $orderDataHandler,
        \Webbhuset\CollectorBankCheckout\Config\Source\Country\Country $countryData
    ) {
        $this->scopeConfig      = $scopeConfig;
        $this->encryptor        = $encryptor;
        $this->checkoutSession  = $checkoutSession;
        $this->storeManager     = $storeManager;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->orderDataHandler = $orderDataHandler;
        $this->countryData      = $countryData;
    }

    /**
     * Returns true if collector payment method is active
     *
     * @return bool
     */
    public function getIsActive(): bool
    {
        return 1 == $this->getConfigValue('active');
    }

    /**
     * Returns true if customers accounts should be created for new orders
     *
     * @return bool
     */
    public function getCreateCustomerAccount(): bool
    {
        return 1 == $this->getConfigValue('create_customer_account');
    }

    /**
     * Returns an array of all config variables
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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

    /**
     * Get the username
     *
     * @return string
     */
    public function getUsername() : string
    {
        return $this->getIsTestMode() ? $this->getTestModeUsername() : $this->getProductionModeUsername();
    }

    /**
     * Get shared access key
     *
     * @return string
     */
    public function getSharedAccessKey() : string
    {
        return $this->getPassword();
    }

    /**
     * Get shared access key / password
     *
     * @return string
     */
    public function getPassword() : string
    {
        return $this->getIsTestMode() ? $this->getTestModePassword() : $this->getProductionModePassword();
    }

    /**
     * Get country code
     *
     * @return string
     */
    public function getCountryCode() : string
    {
        return $this->getConfigValue('country_code');
    }

    /**
     * Sets store id on the config object
     *
     * @param $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Gets current store id
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId() : string
    {
        if ($this->storeId) {
            return $this->storeId;
        }

        return $this->getCustomerStoreId();
    }

    /**
     * Gets B2C store id
     *
     * @return string
     */
    public function getB2C() : string
    {
        return $this->getIsTestMode() ? $this->getTestModeB2C() : $this->getProductionModeB2C();
    }

    /**
     * Get B2B store id
     *
     * @return string
     */
    public function getB2B() : string
    {
        return $this->getIsTestMode() ? $this->getTestModeB2B() : $this->getProductionModeB2B();
    }

    /**
     * Get customer types allowed to checkout
     *
     * @return int
     */
    public function getCustomerTypeAllowed(): int
    {
        return $this->getConfigValue('customer_type') ? $this->getConfigValue('customer_type') : 0;
    }

    /**
     * Get default customer type
     *
     * @return int
     */
    public function getDefaultCustomerType(): int
    {
        return $this->getConfigValue('default_customer_type') ? $this->getConfigValue('default_customer_type') : 0;
    }

    /**
     * Returns true if in mock mode
     *
     * @return bool
     */
    public function getIsMockMode(): bool
    {
        return false;
    }

    /**
     * Returns true if in test mode
     *
     * @return bool
     */
    public function getIsTestMode(): bool
    {
        return $this->getConfigValue('test_mode') ? $this->getConfigValue('test_mode') : false;
    }

    /**
     * Get the url for customer / merchant terms
     *
     * @return string
     */
    public function getMerchantTermsUri(): string
    {
        return $this->getConfigValue('terms_url') ? $this->getConfigValue('terms_url') : "";
    }

    /**
     * Get the redirect page url = Success page / thank you page url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRedirectPageUri(): string
    {
        $checkoutUrl = \Webbhuset\CollectorBankCheckout\Gateway\Config::CHECKOUT_URL_KEY;
        $urlKey = $checkoutUrl . "/success/index/reference/{checkout.publictoken}";

        $url = $this->storeManager->getStore()->getUrl($urlKey);

        return $url;
    }

    /**
     * Get the notification url - Used by collector to update order state after order has been placed
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getNotificationUri() : string
    {
        $urlKey = "collectorbank/notification/index/reference/{checkout.publictoken}";

        if ($this->getCustomBaseUrl()) {
            return $this->getCustomBaseUrl() . $urlKey;
        }

        return $this->storeManager->getStore()->getUrl($urlKey);
    }

    /**
     * Get the validation url - Used by collector when placing orders
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getValidationUri(): string
    {
        $urlKey = "collectorbank/validation/index/reference/{checkout.publictoken}";

        if ($this->getCustomBaseUrl()) {
            return $this->getCustomBaseUrl() . $urlKey;
        }

        return $this->storeManager->getStore()->getUrl($urlKey);
    }

    /**
     * Get the order status for new orders
     *
     * @return string
     */
    public function getOrderStatusNew(): string
    {
        return $this->getConfigValue('order_status');
    }

    /**
     * Get the order status for acknowledged
     *
     * @return string
     */
    public function getOrderStatusAcknowledged(): string
    {
        return $this->getConfigValue('order_accepted_status');
    }

    /**
     * Get the order status for holded
     *
     * @return string
     */
    public function getOrderStatusHolded(): string
    {
        return $this->getConfigValue('order_holded_status');
    }

    /**
     * Get the order status for denied
     *
     * @return string
     */
    public function getOrderStatusDenied(): string
    {
        return $this->getConfigValue('order_denied_status');
    }

    /**
     * Get profile name
     *
     * @return string
     */
    public function getProfileName(): string
    {
        $profileName = $this->getConfigValue('profile_name');
        return $profileName ? $profileName : "";
    }

    /**
     * Get production mode username
     *
     * @return string
     */
    public function getProductionModeUsername(): string
    {
        return $this->getConfigValue('username') ? $this->getConfigValue('username') : "";
    }

    /**
     * Get production mode password / shared secret
     *
     * @return string
     */
    public function getProductionModePassword(): string
    {
        $value = $this->getConfigValue('password');
        if (!$value) {
            return "";
        }

        $value = $this->encryptor->decrypt($value);

        return $value;
    }

    /**
     * Get production mode store id for B2C
     *
     * @return string
     */
    public function getProductionModeB2C() : string
    {
        return $this->getConfigValue('b2c') ? $this->getConfigValue('b2c') : "";
    }

    /**
     * Get production mode store id for B2B
     *
     * @return string
     */
    public function getProductionModeB2B() : string
    {
        return $this->getConfigValue('b2b') ? $this->getConfigValue('b2b') : "";
    }

    /**
     * Get username for testmode
     *
     * @return string
     */
    public function getTestModeUsername(): string
    {
        return $this->getConfigValue('test_mode_username') ? $this->getConfigValue('test_mode_username') : "";
    }

    /**
     * Get password for testmode
     *
     * @return string
     */
    public function getTestModePassword(): string
    {
        $value = $this->getConfigValue('test_mode_password');
        if (!$value) {
            return "";
        }
        $value = $this->encryptor->decrypt($value);

        return $value;
    }

    /**
     * Get storeid for b2b for testmode
     *
     * @return string
     */
    public function getTestModeB2C(): string
    {
        return $this->getConfigValue('test_mode_b2c') ? $this->getConfigValue('test_mode_b2c') : "";
    }

    /**
     * Get storeid for b2b for testmode
     *
     * @return string
     */
    public function getTestModeB2B(): string
    {
        return $this->getConfigValue('test_mode_b2b') ? $this->getConfigValue('test_mode_b2b') : "";
    }

    private function getConfigValue($name)
    {
        $value = $this->scopeConfig->getValue(
            'payment/collectorbank_checkout/configuration/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $value;
    }

    /**
     * Get the current mode the collector bank payment method is running in
     *
     * @return string
     */
    public function getMode()
    {
        $mode = $this->getIsTestMode() ? "test mode" : "production mode";

        return $this->getIsMockMode() ? "mock mode" : $mode;
    }

    /**
     * Returns true if collector bank is in testmode
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->getIsTestMode();
    }

    /**
     * Returns true if collector bank is in production mode
     *
     * @return bool
     */
    public function isProductionMode(): bool
    {
        return !$this->getIsTestMode();
    }

    /**
     * Get custom base url - used one behind a proxy / firewall
     *
     * @return mixed
     */
    public function getCustomBaseUrl()
    {
        return $this->getConfigValue('custom_base_url');
    }

    /**
     * Get the current customer type
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerType()
    {
        $quote = $this->checkoutSession->getQuote();
        $customerType = $this->quoteDataHandler->getCustomerType($quote);

        if ($customerType) {
            return $customerType;
        }

        return $this->getDefaultCustomerType();
    }

    /**
     * Get the store id to be used for the current customer
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerStoreId()
    {
        $customerType = $this->getCustomerType();

        if (\Webbhuset\CollectorBankCheckout\Config\Source\Customer\DefaultType::PRIVATE_CUSTOMERS == $customerType) {
            return $this->getB2C();
        } else {
            return $this->getB2B();
        }
    }

    /**
     * Get collector bank store id for an order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     */
    public function getStoreIdForOrder(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $customerType = $this->orderDataHandler->getStoreId($order);
        if (AllowedCustomerType::PRIVATE_CUSTOMERS == $customerType) {
            return $this->getB2C();
        }

        return $this->getB2B();
    }

    /**
     * Get checkout url
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCheckoutUrl()
    {
        $urlKey = \Webbhuset\CollectorBankCheckout\Gateway\Config::CHECKOUT_URL_KEY;
        $url = $this->storeManager->getStore()->getUrl($urlKey);

        return $url;
    }

    /**
     * Get style data-lang an attribute used for styling iframe
     *
     * @return mixed
     */
    public function getStyleDataLang()
    {
        $data = $this->getConfigValue('style_data_lang');

        return ($data) ? $data : $this->getDefaultLanguage();
    }

    /**
     * Get default language code for the selected country
     *
     *
     * @return mixed
     */
    public function getDefaultLanguage()
    {
        $language = $this->countryData->getDefaultLanguagePerCounty();
        $countryCode = $this->getCountryCode();

        return $language[$countryCode];
    }

    /**
     * Get style data-padding, an attribute used for styling iframe
     *
     * @return mixed|null
     */
    public function getStyleDataPadding()
    {
        $data = $this->getConfigValue('style_data_padding');

        return ($data) ? $data : null;
    }

    /**
     * Get style container-id, an attribute used for styling iframe
     *
     * @return mixed|null
     */
    public function getStyleDataContainerId()
    {
        $data = $this->getConfigValue('style_data_container_id');

        return ($data) ? $data : null;
    }

    /**
     * Get style data-action-color, an attribute used for styling iframe
     *
     * @return mixed|null
     */
    public function getStyleDataActionColor()
    {
        $data = $this->getConfigValue('style_data_action_color');

        return ($data) ? $data : null;
    }

    /**
     * Get style data-action-text-color, an attribute used for styling iframe
     *
     * @return mixed|null
     */
    public function getStyleDataActionTextColor()
    {
        $data = $this->getConfigValue('style_data_action_text_color');

        return ($data) ? $data : null;
    }

    /**
     * Get default currency code for the selected country
     *
     * @return mixed
     */
    public function getCurrency()
    {
        $currencies = $this->countryData->getCurrencyPerCountry();
        $countryCode = $this->getCountryCode();

        return $currencies[$countryCode];
    }
}
