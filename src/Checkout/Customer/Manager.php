<?php

namespace Webbhuset\CollectorBankCheckout\Checkout\Customer;

class Manager
{
    protected $customerInterface;
    protected $storeManager;
    protected $accountManagement;
    protected $customerRepository;
    protected $quoteRepository;
    protected $config;

    public function __construct(
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerInterface,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config
    ) {
        $this->customerInterface  = $customerInterface;
        $this->accountManagement  = $accountManagement;
        $this->storeManager       = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->quoteRepository    = $quoteRepository;
        $this->config             = $config;
    }

    public function handleCustomerOnQuote(\Magento\Quote\Model\Quote $quote)
    {
        $customer = $this->getOrCreateCustomerIfConfigured($quote);
        if ($customer) {
            $this->saveCustomerOnQuote($quote, $customer);
        }
    }

    public function getOrCreateCustomerIfConfigured(
        \Magento\Quote\Model\Quote $quote
    ) {
        $config = $this->config->create();
        $customer = $this->getCustomerByEmail($quote->getCustomerEmail());

        if ($customer->getId()) {
            return $customer;
        }
        if (!$config->getCreateCustomerAccount()) {
            return false;
        }

        return $this->createCustomerFromQuote($quote);
    }

    public function createCustomerFromQuote(
        \Magento\Quote\Model\Quote $quote
    ) {
        $email = $quote->getCustomerEmail();
        $customer = $this->customerInterface->create();

        $websiteId  = $this->storeManager->getWebsite()->getId();

        $customer->setWebsiteId($websiteId)
            ->setLastname($quote->getCustomerLastname())
            ->setFirstname($quote->getCustomerFirstname())
            ->setEmail($email);
        return $this->accountManagement->createAccount($customer);
    }

    public function getCustomerByEmail($email): \Magento\Customer\Api\Data\CustomerInterface
    {
        $websiteId  = $this->storeManager->getWebsite()->getId();

        if (!$this->accountManagement->isEmailAvailable($email, $websiteId)) {
            return $this->customerRepository->get($email, $websiteId);
        }

        return $this->customerInterface->create();
    }

    public function saveCustomerOnQuote(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Customer\Api\Data\CustomerInterface $customer
    ) {
        $quote = $quote->setCustomer($customer);
        $this->quoteRepository->save($quote);
    }
}
