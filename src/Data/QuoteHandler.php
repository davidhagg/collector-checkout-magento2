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
        return $quote->getCollectorbankCustomerType();
    }

    public function setCustomerType(Quote $quote, $customerType)
    {
        $quote->setCollectorbankCustomerType($customerType);

        return $this;
    }

    public function getData(Quote $quote)
    {
        $data = json_decode($quote->getCollectorbankData());

        return ($data) ? get_object_vars($data) : [];
    }

    public function setData(Quote $quote, $data)
    {
        $quote->setCollectorbankData(json_encode($data));

        return $this;
    }

    public function setOrgNumber(Quote $quote, $orgNumber)
    {
        return $this->setAdditionalData($quote, 'org_number', $orgNumber);
    }

    public function getOrgNumber(Quote $quote)
    {
        return $this->getAdditionalData($quote, 'org_number');
    }

    public function setReference(Quote $quote, $reference)
    {
        return $this->setAdditionalData($quote, 'reference', $reference);
    }

    public function getReference(Quote $quote)
    {
        return $this->getAdditionalData($quote, 'reference');
    }

    public function setStoreId(Quote $quote, $reference)
    {
        return $this->setAdditionalData($quote, 'store_id', $reference);
    }

    public function getStoreId(Quote $quote)
    {
        return $this->getAdditionalData($quote, 'store_id');
    }

    public function setNewsletterSubscribe(Quote $quote, int $subscribe)
    {
        return $this->setAdditionalData($quote, 'newsletter_subscribe', $subscribe);
    }

    public function getNewsletterSubscribe(Quote $quote):bool
    {
        return $this->getAdditionalData($quote, 'newsletter_subscribe');
    }

    private function getAdditionalData(Quote $quote, string $name)
    {
        $data = $this->getData($quote);
        if (!isset($data[$name])) {
            return null;
        }

        return $data[$name];
    }

    private function setAdditionalData(Quote $quote, string $name, string $value)
    {
        $data = $this->getData($quote);
        $data[$name] = $value;

        return $this->setData($quote, $data);
    }
}
