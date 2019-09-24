<?php

namespace Webbhuset\CollectorBankCheckout;

class Adapter
{
    protected $quoteConverter;
    protected $config;
    protected $quoteDataHandler;
    protected $orderDataHandler;
    protected $quoteUpdater;
    protected $quoteRepository;
    protected $logger;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorBankCheckout\QuoteUpdater $quoteUpdater,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Data\OrderHandler $orderDataHandler,
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger
    ) {
        $this->quoteConverter   = $quoteConverter;
        $this->config           = $config;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->orderDataHandler = $orderDataHandler;
        $this->quoteUpdater     = $quoteUpdater;
        $this->quoteRepository  = $quoteRepository;
        $this->logger           = $logger;
    }

    public function initOrSync(\Magento\Quote\Model\Quote $quote) : string
    {
        $publicToken = $this->quoteDataHandler->getPublicToken($quote);
        if ($publicToken) {
            $this->synchronize($quote);
        } else {
            $collectorSession = $this->initialize($quote);
            $publicToken = $collectorSession->getPublicToken();
        }

        return $publicToken;
    }

    /**
     * Fetch addresses from collector order,
     * set address on magento quote,
     * update fees and cart if needed
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Exception
     */
    public function synchronize(\Magento\Quote\Model\Quote $quote)
    {
        $checkoutData = $this->acquireCheckoutInformationFromQuote($quote);
        $oldFees = $checkoutData->getFees();
        $oldCart = $checkoutData->getCart();
        $quote = $this->quoteUpdater->setQuoteData($quote, $checkoutData);
        $shippingAddress = $quote->getShippingAddress();

        if (!$shippingAddress->getShippingMethod()) {
            $this->quoteUpdater->setDefaultShippingMethod($quote);
        }

        $quote->collectTotals()
            ->save(); // Save observer triggers update fees and update cart

        return $quote;
    }

    public function initialize(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $quote = $this->quoteUpdater->setDefaultShippingIfEmpty($quote);
        $this->quoteRepository->save($quote);

        $cart = $this->quoteConverter->getCart($quote);
        $fees = $this->quoteConverter->getFees($quote);
        $initCustomer = $this->quoteConverter->getInitializeCustomer($quote);

        $config = $this->getConfig($this->config->getStoreId());

        $countryCode = $config->getCountryCode();
        $adapter = $this->getAdapter($config);

        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);

        try {
            $collectorSession->initialize(
                $config,
                $fees,
                $cart,
                $countryCode,
                $initCustomer
            );

            $this->quoteDataHandler->setPrivateId($quote, $collectorSession->getPrivateId())
                ->setPublicToken($quote, $collectorSession->getPublicToken())
                ->setCustomerType($quote, $this->config->getCustomerType());

            $this->quoteRepository->save($quote);
        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {
            $this->logger->addCritical("Response error when initiating iframe " . $e->getMessage());
            die;
        }

        return $collectorSession;
    }

    public function acquireCheckoutInformationFromQuote(\Magento\Quote\Model\Quote $quote): \CollectorBank\CheckoutSDK\CheckoutData
    {
        $privateId = $this->quoteDataHandler->getPrivateId($quote);
        $data = $this->acquireCheckoutInformation($privateId);

        return $data;
    }

    public function acquireCheckoutInformationFromOrder(\Magento\Quote\Model\Quote $order): \CollectorBank\CheckoutSDK\CheckoutData
    {
        $privateId = $this->orderDataHandler->getPrivateId($order);

        return $this->acquireCheckoutInformation($privateId);
    }

    public function acquireCheckoutInformation($privateId, $storeId = 0): \CollectorBank\CheckoutSDK\CheckoutData
    {
        $config = $this->getConfig($storeId);
        $adapter = $this->getAdapter($config);

        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);
        $collectorSession->load($privateId);

        return $collectorSession->getCheckoutData();
    }

    public function updateFees(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $config = $this->getConfig($this->config->getCustomerStoreId());
        $adapter = $this->getAdapter($config);
        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);

        $fees = $this->quoteConverter->getFees($quote);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);

        try {
            if (!empty($fees->toArray())) {
                $collectorSession->setPrivateId($privateId)
                    ->updateFees($fees);
            }
        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {
            $this->logger->addCritical("Response error when updating fees. " . $e->getMessage());
            die;
        }

        return $collectorSession;
    }

    public function updateCart(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $config = $this->getConfig($this->config->getCustomerStoreId());
        $adapter = $this->getAdapter($config);
        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);
        $cart = $this->quoteConverter->getCart($quote);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);

        try {
            if (empty($cart->getItems())) {
                $collectorSession->setPrivateId($privateId)
                    ->updateCart($cart);
            }
        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {
            $this->logger->addCritical("Response error when updating cart. " . $e->getMessage());
            die;
        }

        return $collectorSession;
    }

    public function getConfig($storeId = null) : \CollectorBank\CheckoutSDK\Config\ConfigInterface
    {
        if ($storeId) {
            $this->config->setStoreId($storeId);
        }

        return $this->config;
    }

    public function getAdapter($config) : \CollectorBank\CheckoutSDK\Adapter\AdapterInterface
    {
        $config = $this->getConfig();

        if ($config->getIsMockMode()) {
            return new \CollectorBank\CheckoutSDK\Adapter\MockAdapter($config);
        }

        return new \CollectorBank\CheckoutSDK\Adapter\CurlAdapter($config);
    }
}
