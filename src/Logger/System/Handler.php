<?php

namespace Webbhuset\CollectorBankCheckout\Logger\Handler;

class System extends \Magento\Framework\Logger\Handler\System
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/collectorbank.log';
}
