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

    public function __construct(
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorBankCheckout\QuoteUpdater $quoteUpdater,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Data\OrderHandler $orderDataHandler,
        \Webbhuset\CollectorBankCheckout\Config\Config $config
    ) {
        $this->quoteConverter   = $quoteConverter;
        $this->config           = $config;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->orderDataHandler = $orderDataHandler;
        $this->quoteUpdater     = $quoteUpdater;
        $this->quoteRepository  = $quoteRepository;
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
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Model\Quote
     */
    public function synchronize(\Magento\Quote\Model\Quote $quote)
    {
        $checkoutData = $this->acquireCheckoutInformationFromQuote($quote);
        $oldFees = $checkoutData->getFees();
        $oldCart = $checkoutData->getCart();

        $quote = $this->quoteUpdater->setQuoteData($quote, $checkoutData);
        $quote->collectTotals()
            ->save();

        $newFees = $this->quoteConverter->getFees($quote);
        if ($oldFees != $newFees) {
            $this->updateFees($quote);
        }

        $newCart = $this->quoteConverter->getCart($quote);
        if ($oldCart != $newCart) {
            $this->updateCart($quote);
        }

        return $quote;
    }

    public function initialize(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $this->quoteUpdater->setDefaultShippingIfEmpty($quote);
        $this->quoteRepository->save($quote);

        $cart = $this->quoteConverter->getCart($quote);
        $fees = $this->quoteConverter->getFees($quote);
        $initCustomer = $this->quoteConverter->getInitializeCustomer($quote);

        $config = $this->getConfig($quote->getStoreId());
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
                ->setStoreId($quote, $config->getStoreId());

            $this->quoteRepository->save($quote);

        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {

            die;
        }

        return $collectorSession;
    }

    public function acquireCheckoutInformationFromQuote(\Magento\Quote\Model\Quote $quote): \CollectorBank\CheckoutSDK\CheckoutData
    {
        $privateId = $this->quoteDataHandler->getPrivateId($quote);

        return $this->acquireCheckoutInformation($privateId);
    }

    public function acquireCheckoutInformationFromOrder(\Magento\Sales\Model\Order $order): \CollectorBank\CheckoutSDK\CheckoutData
    {
        $privateId = $this->orderDataHandler->getPrivateId($order);

        return $this->acquireCheckoutInformation($privateId);
    }

    public function acquireCheckoutInformation($privateId): \CollectorBank\CheckoutSDK\CheckoutData
    {
        $config = $this->getConfig();
        $adapter = $this->getAdapter($config);
        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);
        $collectorSession->load($privateId);

        return $collectorSession->getCheckoutData();
    }

    public function updateFees(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $config = $this->getConfig($quote->getStoreId());
        $adapter = $this->getAdapter($config);
        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);

        $fees = $this->quoteConverter->getFees($quote);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);;

        try {
            $collectorSession->setPrivateId($privateId)
                ->updateFees($fees);

        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {
            die;
        }

        return $collectorSession;
    }

    public function updateCart(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $config = $this->getConfig($quote->getStoreId());
        $adapter = $this->getAdapter($config);
        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);

        $cart = $this->quoteConverter->getCart($quote);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);

        try {
            $collectorSession->setPrivateId($privateId)
                ->updateCart($cart);

        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {
            die;
        }

        return $collectorSession;
    }

    public function getConfig($storeId = null) : \CollectorBank\CheckoutSDK\Config\ConfigInterface
    {
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
