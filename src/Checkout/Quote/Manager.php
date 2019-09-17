<?php

namespace Webbhuset\CollectorBankCheckout\Checkout\Quote;

class Manager
{
    protected $searchCriteriaBuilder;
    protected $quoteRepository;
    protected $logger;

    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    public function getQuoteByPublicToken($publicToken): \Magento\Quote\Api\Data\CartInterface
    {
        return $this->getColumnFromQuote("collectorbank_public_id", $publicToken);
    }

    protected function getColumnFromQuote($column, $value): \Magento\Quote\Api\Data\CartInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($column, $value, 'eq')->create();

        $quoteList = $this->quoteRepository->getList($searchCriteria)->getItems();

        if (sizeof($quoteList) == 0) {
            $this->logger->addCritical("Could not find a quotes with column: : $column : value $value and quote-table");

            throw new \Magento\Framework\Exception\NoSuchEntityException();
        }

        return reset($quoteList);
    }

    public function activateQuote(\Magento\Quote\Api\Data\CartInterface $quote): \Magento\Quote\Api\Data\CartInterface
    {
        $quote->setIsActive(1)
            ->setReservedOrderId(null);
        $this->quoteRepository->save($quote);
        return $quote;
    }
}
