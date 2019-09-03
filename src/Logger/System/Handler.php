<?php

namespace Webbhuset\CollectorBankCheckout\Logger\System;

class Handler extends \Magento\Framework\Logger\Handler\System
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/collectorbank.log';
    protected $loggerType = \Monolog\Logger::INFO;
}
