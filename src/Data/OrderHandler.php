<?php

namespace Webbhuset\CollectorBankCheckout\Data;

use \Magento\Sales\Api\Data\OrderInterface as Order;

class OrderHandler
{
    public function getPrivateId(Order $order)
    {
        return $order->getCollectorbankPrivateId();
    }

    public function setPrivateId(Order $order, $id)
    {
        $order->setCollectorbankPrivateId($id);

        return $order;
    }

    public function getPublicToken(Order $order)
    {
        return $order->getCollectorbankPublicId();
    }

    public function setPublicToken(Order $order, $id)
    {
        $order->setCollectorbankPublicId($id);

        return $order;
    }

    public function getStoreId(Order $order)
    {
        return $order->getCollectorbankStoreId();
    }

    public function setStoreId(Order $order, $id)
    {
        $order->setCollectorbankStoreId($id);

        return $order;
    }
}
