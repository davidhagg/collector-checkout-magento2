<?php

namespace Webbhuset\CollectorCheckout\Checkout\Customer;

/**
 * Class Manager
 *
 * @package Webbhuset\CollectorCheckout\Checkout\Customer
 */
class Manager
{
    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    protected $customerInterface;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $accountManagement;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Webbhuset\CollectorCheckout\Config\ConfigFactory
     */
    protected $config;

    /**
     * Manager constructor.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory   $customerInterface
     * @param \Magento\Customer\Api\CustomerRepositoryInterface     $customerRepository
     * @param \Magento\Customer\Api\AccountManagementInterface      $accountManagement
     * @param \Magento\Store\Model\StoreManagerInterface            $storeManager
     * @param \Magento\Quote\Api\CartRepositoryInterface            $quoteRepository
     * @param \Webbhuset\CollectorCheckout\Config\ConfigFactory $config
     */
    public function __construct(
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerInterface,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorCheckout\Config\ConfigFactory $config
    ) {
        $this->customerInterface  = $customerInterface;
        $this->accountManagement  = $accountManagement;
        $this->storeManager       = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->quoteRepository    = $quoteRepository;
        $this->config             = $config;
    }

    /**
     * Adds customer to quote:
     *   if set in admin (create new customer on order)
     *   or
     *   if a customer already exists with that email adress
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function handleCustomerOnQuote(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $customer = $this->getOrCreateCustomerIfConfigured($quote);
        if ($customer) {
            $this->saveCustomerOnQuote($quote, $customer);
        }
    }

    /**
     * If the email address in the quote already exists as a customer then returns customer object
     * If the admin option is set create new customer on order is set to yes then creates a customer object and returns it
     * Otherwise returns false
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
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

    /**
     * Creates a customer based on the data saved in the quote.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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

    /**
     * Returns customer by email for the current website
     *
     * @param $email
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerByEmail($email): \Magento\Customer\Api\Data\CustomerInterface
    {
        $websiteId  = $this->storeManager->getWebsite()->getId();

        if (!$this->accountManagement->isEmailAvailable($email, $websiteId)) {
            return $this->customerRepository->get($email, $websiteId);
        }

        return $this->customerInterface->create();
    }

    /**
     * Saves/sets the the customer on the quote
     *
     * @param \Magento\Quote\Api\Data\CartInterface        $quote
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     */
    public function saveCustomerOnQuote(
        \Magento\Quote\Api\Data\CartInterface $quote,
        \Magento\Customer\Api\Data\CustomerInterface $customer
    ) {
        $quote = $quote->setCustomer($customer);
        $this->quoteRepository->save($quote);
    }
}
