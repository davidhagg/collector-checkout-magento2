<?php

namespace Webbhuset\CollectorBankCheckout\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface as CommandInterface;

class CollectorBankCommand implements CommandInterface
{

    protected $method;

    public function __construct($client)
    {
        $this->method = $client['method'];
    }

    public function execute(array $commandSubject)
    {
        $method = $this->method;
        if (method_exists($this, $method)) {
            call_user_func([$this, $method], $commandSubject);
        }
    }

    public function capture($data)
    {
        return true;
    }

    public function authorize($payment)
    {
        return $payment;
    }

    public function void($data)
    {
        return true;
    }

    public function cancel($payment)
    {
        return true;
    }
}