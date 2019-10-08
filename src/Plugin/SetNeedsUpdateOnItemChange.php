<?php

namespace Webbhuset\CollectorBankCheckout\Plugin;

/**
 * Class SetNeedsUpdateOnItemChange
 *
 * @package Webbhuset\CollectorBankCheckout\Plugin
 */
class SetNeedsUpdateOnItemChange
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
     * SetNeedsUpdateOnItemChange constructor.
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
     * Plugin function to set a flag that collector bank needs update if items has been removed
     *
     * @param \Magento\Checkout\Model\Cart $subject
     * @param                              $result
     * @return mixed
     */
    public function afterRemoveItem(
        \Magento\Checkout\Model\Cart $subject,
        $result
    ) {
        $subject->getQuote()->setNeedsCollectorUpdate(true);

        return $result;
    }

    /**
     * Plugin function to set a flag that collector bank needs update if items has been updated
     *
     * @param \Magento\Checkout\Model\Cart $subject
     * @param                              $result
     * @return mixed
     */
    public function afterUpdateItems(
        \Magento\Checkout\Model\Cart $subject,
        $result
    ) {
        $subject->getQuote()->setNeedsCollectorUpdate(true);

        return $result;
    }
}
