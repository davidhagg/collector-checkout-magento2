<?php

namespace Webbhuset\CollectorBankCheckout\Block\Checkout;

use Webbhuset\CollectorBankCheckout\Config\Source\Customer\DefaultType as CustomerType;
use Webbhuset\CollectorBankCheckout\Config\Source\Customer\Type as AllowedCustomerType;

/**
 * Class CustomerTypeSwitcher
 *
 * @package Webbhuset\CollectorBankCheckout\Block\Checkout
 */
class CustomerTypeSwitcher extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Webbhuset\CollectorBankCheckout\Config\Config
     */
    protected $config;

    /**
     * CustomerTypeSwitcher constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Webbhuset\CollectorBankCheckout\Config\Config   $config
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * Returns the url to the checkout for business customers.
     *
     * @return string
     */
    public function getBusinessCheckoutUrl()
    {
        return $this->config->getCheckoutUrl() . '?customerType=' . CustomerType::BUSINESS_CUSTOMERS;
    }

    /**
     * Returns the url to the checkout for private customers.
     *
     * @return string
     */
    public function getPrivateCheckoutUrl()
    {
        return $this->config->getCheckoutUrl() . '?customerType=' . CustomerType::PRIVATE_CUSTOMERS;
    }

    /**
     * Returns the customer type that is set on the quote, falls back on default customer type set in admin if quote
     * has no customer type specified.
     *
     * @return int
     */
    public function getCustomerType()
    {
        return $this->config->getDefaultCustomerType();
    }

    /**
     * Returns an array with data to render in the private / business customer switch
     *
     * @return array
     */
    public function getAllowedCustomerTypesData()
    {
        $allowedCustomerTypes = $this->config->getCustomerTypeAllowed();
        $allowed = [];

        $allowed[AllowedCustomerType::PRIVATE_CUSTOMERS]['checkoutUrl'] = $this->getPrivateCheckoutUrl();
        $allowed[AllowedCustomerType::PRIVATE_CUSTOMERS]['title'] = __('Private');

        $allowed[AllowedCustomerType::BUSINESS_CUSTOMERS]['checkoutUrl'] = $this->getBusinessCheckoutUrl();
        $allowed[AllowedCustomerType::BUSINESS_CUSTOMERS]['title'] = __('Business');

        switch ($allowedCustomerTypes) {
            case AllowedCustomerType::BUSINESS_CUSTOMERS:
                unset($allowed[AllowedCustomerType::PRIVATE_CUSTOMERS]);
                break;
            case AllowedCustomerType::PRIVATE_CUSTOMERS:
                unset($allowed[AllowedCustomerType::BUSINESS_CUSTOMERS]);
                break;
        }

        $allowed[$this->getCustomerType()]['isActive'] = 1;

        return $allowed;
    }
}
