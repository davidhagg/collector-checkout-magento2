<?php

namespace Webbhuset\CollectorBankCheckout\Data;

use Magento\Quote\Api\Data\CartInterface as Quote;

class QuoteHandler
{
    public function getPrivateId(Quote $quote)
    {
        return $quote->getCollectorbankPrivateId();
    }

    public function setPrivateId(Quote $quote, $id)
    {
        $quote->setCollectorbankPrivateId($id);

        return $this;
    }

    public function getPublicToken(Quote $quote)
    {
        return $quote->getCollectorbankPublicId();
    }

    public function setPublicToken(Quote $quote, $id)
    {
        $quote->setCollectorbankPublicId($id);

        return $this;
    }

    public function getCustomerType(Quote $quote)
    {
        return $quote->getCollectorbankStoreId();
    }

    public function setCustomerType(Quote $quote, $customerType)
    {
        $quote->setCollectorbankStoreId($customerType);

        return $this;
    }
}
