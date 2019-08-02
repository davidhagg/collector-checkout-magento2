<?php

namespace Webbhuset\CollectorBankCheckout\Data;

use Magento\Quote\Model\Quote;

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

    public function getStoreId(Quote $quote)
    {
        return $quote->getCollectorbankStoreId();
    }

    public function setStoreId(Quote $quote, $id)
    {
        $quote->setCollectorbankStoreId($id);

        return $this;
    }
}
