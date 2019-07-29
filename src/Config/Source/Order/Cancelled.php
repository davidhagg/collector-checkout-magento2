<?php

namespace Webbhuset\CollectorBankCheckout\Config\Source\Order;

/**
 * Order Statuses source model
 */
class Cancelled extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        \Magento\Sales\Model\Order::STATE_CANCELED,
    ];
}
