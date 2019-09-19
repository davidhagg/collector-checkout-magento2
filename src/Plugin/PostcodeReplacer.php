<?php

namespace Webbhuset\CollectorBankCheckout\Plugin;

class PostcodeReplacer
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
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        if (
            $this->quoteDataHandler->getPublicToken($quote)
            && $this->config->getIsActive($quote->getStoreId())
        ) {
            $shippingAddress = $quote->getShippingAddress();

            $addressInformation->getShippingAddress()
                ->setPostcode($shippingAddress->getPostcode());
        }

        return [$cartId, $addressInformation];
    }

    public function beforeCalculate($subject, $cartId, \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        if (
            $this->quoteDataHandler->getPublicToken($quote)
            && $this->config->getIsActive($quote->getStoreId())
        ) {
            $shippingAddress = $quote->getShippingAddress();

            $addressInformation->getAddress()
                ->setPostcode($shippingAddress->getPostcode());
        }

        return [$cartId, $addressInformation];
    }
}