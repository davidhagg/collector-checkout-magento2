<?php

namespace Webbhuset\CollectorBankCheckout\Data;

use Magento\Sales\Model\Order;

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

    public function getStoreId(Order $order)
    {
        return $order->getCollectorbankStoreId();
    }

    public function setStoreId(Order $order, $id)
    {
        $order->setCollectorbankStoreId($id);

        return $this;
    }
}
