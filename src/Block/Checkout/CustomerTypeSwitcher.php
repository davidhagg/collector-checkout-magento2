<?php

namespace Webbhuset\CollectorBankCheckout\Block\Checkout;

use \Webbhuset\CollectorBankCheckout\Config\Source\Customer\Type as AllowedCustomerType;
use \Webbhuset\CollectorBankCheckout\Config\Source\Customer\DefaultType as CustomerType;

class CustomerTypeSwitcher extends \Magento\Framework\View\Element\Template
{
    protected $config;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    public function getBusinessCheckoutUrl()
    {
        return $this->config->getCheckoutUrl() . '?customerType=' . CustomerType::BUSINESS_CUSTOMERS;
    }

    public function getPrivateCheckoutUrl()
    {
        return $this->config->getCheckoutUrl() . '?customerType=' . CustomerType::PRIVATE_CUSTOMERS;
    }

    public function getCustomerType()
    {
        return $this->config->getCustomerType();
    }

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
