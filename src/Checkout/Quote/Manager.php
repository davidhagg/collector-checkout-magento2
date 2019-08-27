<?php

namespace Webbhuset\CollectorBankCheckout\Checkout\Quote;

class Manager
{
    protected $searchCriteriaBuilder;
    protected $quoteRepository;

    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->quoteRepository = $quoteRepository;
    }

    public function getQuoteByPublicToken($publicToken): \Magento\Quote\Api\Data\CartInterface
    {
        return $this->getColumnFromQuote("collectorbank_public_id", $publicToken);
    }

    private function getColumnFromQuote($column, $value): \Magento\Quote\Api\Data\CartInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($column, $value, 'eq')->create();

        $quoteList = $this->quoteRepository->getList($searchCriteria)->getItems();

        if (sizeof($quoteList) == 0) {
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