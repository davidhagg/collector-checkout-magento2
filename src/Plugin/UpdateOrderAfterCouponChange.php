<?php

namespace Webbhuset\CollectorBankCheckout\Plugin;

/**
 * Class UpdateOrderAfterCouponChange
 *
 * @package Webbhuset\CollectorBankCheckout\Plugin
 */
class UpdateOrderAfterCouponChange
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
     * UpdateOrderAfterCouponChange constructor.
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
     * Plugin function to set a flag that collector bank needs update if coupon has been set
     *
     * @param \Magento\Quote\Model\CouponManagement $subject
     * @param callable                              $proceed
     * @param mixed                                 ...$args
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundSet(
        \Magento\Quote\Model\CouponManagement $subject,
        callable $proceed,
        ...$args
    ) {
        $cartId = reset($args);
        $this->setNeedsCollectorUpdate($cartId);

        return $proceed(...$args);
    }

    /**
     * Plugin function to set a flag that collector bank needs update if coupon has been set
     *
     * @param \Magento\Quote\Model\CouponManagement $subject
     * @param callable                              $proceed
     * @param mixed                                 ...$args
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundRemove(
        \Magento\Quote\Model\CouponManagement $subject,
        callable $proceed,
        ...$args
    ) {
        $cartId = reset($args);
        $this->setNeedsCollectorUpdate($cartId);

        return $proceed(...$args);
    }

    /**
     * Sets a flag on the quote to indicate that the cart needs to be updated in collector bank
     *
     * @param $cartId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
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
