<?php

namespace Webbhuset\CollectorBankCheckout\Plugin;

class UpdateOrderAfterShippingChange
{
    protected $quoteRepository;
    protected $quoteDataHandler;
    protected $config;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorBankCheckout\Config\Config $config
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->config = $config;
    }

    public function aroundSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        callable $proceed,
        ...$args
    ) {
        $cartId = reset($args);
        $this->setNeedsCollectorUpdate($cartId);

        return $proceed(...$args);
    }

    public function setNeedsCollectorUpdate($cartId)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        if (
            $this->quoteDataHandler->getPublicToken($quote)
            && $this->config->getIsActive($quote->getStoreId())
        ) {
            $quote->setNeedsCollectorUpdate(true);
        }
    }
}
