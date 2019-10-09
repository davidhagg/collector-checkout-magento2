<?php

namespace Webbhuset\CollectorBankCheckout;

/**
 * Class Adapter
 *
 * @package Webbhuset\CollectorBankCheckout
 */
class Adapter
{
    /**
     * @var QuoteConverter
     */
    protected $quoteConverter;
    /**
     * @var Config\ConfigFactory
     */
    protected $configFactory;
    /**
     * @var Data\QuoteHandler
     */
    protected $quoteDataHandler;
    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var Logger\Logger
     */
    protected $logger;

    /**
     * Adapter constructor.
     *
     * @param QuoteConverter                             $quoteConverter
     * @param QuoteUpdater                               $quoteUpdater
     * @param Data\QuoteHandler                          $quoteDataHandler
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param Data\OrderHandler                          $orderDataHandler
     * @param Config\Config                              $config
     * @param Logger\Logger                              $logger
     */
    public function __construct(
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorBankCheckout\QuoteUpdater $quoteUpdater,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Config\QuoteConfigFactory $configFactory,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger
    ) {
        $this->quoteConverter   = $quoteConverter;
        $this->configFactory    = $configFactory;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->quoteUpdater     = $quoteUpdater;
        $this->quoteRepository  = $quoteRepository;
        $this->logger           = $logger;
    }

    /**
     * Init or syncs the iframe and updates the necessary data on quote (e.g. public and private token)
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return string
     * @throws \Exception
     */
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

        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates();

        $rate = $shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod());
        if (!$rate || !$shippingAddress->getShippingMethod()) {
            $this->quoteUpdater->setDefaultShippingMethod($quote);
        }

        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        $this->updateFees($quote);
        $this->updateCart($quote);

        return $quote;
    }

    /**
     * Initializes a new iframe
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \CollectorBank\CheckoutSDK\Session
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function initialize(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $config = $this->configFactory->create(['quote' => $quote]);
        $quote = $this->quoteUpdater->setDefaultShippingIfEmpty($quote);
        $this->quoteRepository->save($quote);

        $cart = $this->quoteConverter->getCart($quote);
        $fees = $this->quoteConverter->getFees($quote);
        $initCustomer = $this->quoteConverter->getInitializeCustomer($quote);

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
                ->setCustomerType($quote, $config->getDefaultCustomerType());

            $this->quoteRepository->save($quote);
        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {
            $this->logger->addCritical("Response error when initiating iframe " . $e->getMessage());
            die;
        }

        return $collectorSession;
    }

    /**
     * Acquires information from collector bank about the current session
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \CollectorBank\CheckoutSDK\CheckoutData
     */
    public function acquireCheckoutInformationFromQuote(\Magento\Quote\Model\Quote $quote): \CollectorBank\CheckoutSDK\CheckoutData
    {
        $config = $this->configFactory->create(['quote' => $quote]);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);
        $data = $this->acquireCheckoutInformation($config, $privateId);

        return $data;
    }

    /**
     * Acquires information from collector bank about the current session from privateId
     *
     * @param \Webbhuset\CollectorBankCheckout\Config\QuoteConfig $privateId
     * @param int $privateId
     * @return \CollectorBank\CheckoutSDK\CheckoutData
     */
    public function acquireCheckoutInformation($config, $privateId): \CollectorBank\CheckoutSDK\CheckoutData
    {
        $adapter = $this->getAdapter($config);

        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);
        $collectorSession->load($privateId);

        return $collectorSession->getCheckoutData();
    }

    /**
     * Update fees in the collector bank session
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \CollectorBank\CheckoutSDK\Session
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateFees(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $config = $this->configFactory->create(['quote' => $quote]);
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

    /**
     *
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \CollectorBank\CheckoutSDK\Session
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateCart(\Magento\Quote\Model\Quote $quote) : \CollectorBank\CheckoutSDK\Session
    {
        $config = $this->configFactory->create(['quote' => $quote]);
        $adapter = $this->getAdapter($config);
        $collectorSession = new \CollectorBank\CheckoutSDK\Session($adapter);
        $cart = $this->quoteConverter->getCart($quote);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);

        try {
            if (!empty($cart->getItems())) {
                $collectorSession->setPrivateId($privateId)
                    ->updateCart($cart);
            }
        } catch (\CollectorBank\CheckoutSDK\Errors\ResponseError $e) {
            $this->logger->addCritical("Response error when updating cart. " . $e->getMessage());
            die;
        }

        return $collectorSession;
    }

    /**
     * Get adapter
     *
     * @param \Webbhuset\CollectorBankCheckout\Config\QuoteConfig $config
     * @return \CollectorBank\CheckoutSDK\Adapter\AdapterInterface
     */
    public function getAdapter($config) : \CollectorBank\CheckoutSDK\Adapter\AdapterInterface
    {
        if ($config->getIsMockMode()) {
            return new \CollectorBank\CheckoutSDK\Adapter\MockAdapter($config);
        }

        return new \CollectorBank\CheckoutSDK\Adapter\CurlAdapter($config);
    }
}
