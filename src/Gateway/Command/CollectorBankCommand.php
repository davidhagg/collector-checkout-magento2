<?php

namespace Webbhuset\CollectorBankCheckout\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface as CommandInterface;

class CollectorBankCommand implements CommandInterface
{
    public function execute(array $commandSubject)
    {
        return false;
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