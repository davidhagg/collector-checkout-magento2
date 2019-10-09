<?php

namespace Webbhuset\CollectorBankCheckout\Plugin;

/**
 * Class PostcodeReplacer
 *
 * @package Webbhuset\CollectorBankCheckout\Plugin
 */
class PostcodeReplacer
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Data\QuoteHandler
     */
    protected $quoteDataHandler;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Config\Config
     */
    protected $config;

    /**
     * PostcodeReplacer constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface         $quoteRepository
     * @param \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler
     * @param \Webbhuset\CollectorBankCheckout\Config\Config     $config
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorBankCheckout\Config\Config $config
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->config = $config;

    }

    /**
     *
     *
     * @param \Magento\Checkout\Model\ShippingInformationManagement   $subject
     * @param                                                         $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
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

    /**
     * Sets address if available before calculating totals
     *
     * @param                                                       $subject
     * @param                                                       $cartId
     * @param \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
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