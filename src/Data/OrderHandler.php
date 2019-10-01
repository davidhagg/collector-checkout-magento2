<?php

namespace Webbhuset\CollectorBankCheckout\Data;

use Magento\Sales\Api\Data\OrderInterface as Order;

class OrderHandler
{
    public function getPrivateId(Order $order)
    {
        return $order->getCollectorbankPrivateId();
    }

    public function setPrivateId(Order $order, $id)
    {
        $order->setCollectorbankPrivateId($id);

        return $this;
    }

    public function getPublicToken(Order $order)
    {
        return $order->getCollectorbankPublicId();
    }

    public function setPublicToken(Order $order, $id)
    {
        $order->setCollectorbankPublicId($id);

        return $this;
    }

    public function getCustomerType(Order $order)
    {
        return $order->getCollectorbankCustomerType();
    }

    public function setCustomerType(Order $order, $id)
    {
        $order->setCollectorbankCustomerType($id);

        return $this;
    }

    public function getData(Order $order)
    {
        $data = json_decode($order->getCollectorbankData());

        return ($data) ? get_object_vars($data) : [];
    }

    public function setData(Order $order, $data)
    {
        $order->setCollectorbankData(json_encode($data));

        return $this;
    }

    public function setOrgNumber(Order $order, $orgNumber)
    {
        return $this->setAdditionalData($order, 'org_number', $orgNumber);
    }

    public function getOrgNumber(Order $order)
    {
        return $this->getAdditionalData($order, 'org_number');
    }

    public function setReference(Order $order, $reference)
    {
        return $this->setAdditionalData($order, 'reference', $reference);
    }

    public function getReference(Order $order)
    {
        return $this->getAdditionalData($order, 'reference');
    }

    public function setStoreId(Order $order, $reference)
    {
        return $this->setAdditionalData($order, 'store_id', $reference);
    }

    public function getStoreId(Order $order)
    {
        return $this->getAdditionalData($order, 'store_id');
    }

    private function getAdditionalData(Order $order, string $name)
    {
        $data = $this->getData($order);
        if (!isset($data[$name])) {

            return null;
        }

        return $data[$name];
    }

    public function getNewsletterSubscribe(Order $order):bool
    {
        $newsletterSubscribe = $this->getAdditionalData($order, 'newsletter_subscribe');

        return (1 == (int)$newsletterSubscribe) ? true : false;
    }

    private function setAdditionalData(Order $order, string $name, string $value)
    {
        $data = $this->getData($order);
        $data[$name] = $value;

        return $this->setData($order, $data);
    }
}
