<?php

namespace Webbhuset\CollectorBankCheckout\Invoice;

use CollectorBank\PaymentSDK\Adapter\SoapAdapter;
use CollectorBank\PaymentSDK\Errors\ResponseError;
use CollectorBank\PaymentSDK\Invoice\Administration as InvoiceAdministration;

class Administration
{
    protected $config;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config
    ) {
        $this->config = $config;
    }

    public function activateInvoice(int $invoiceNo, int $orderId):array
    {
        $adapter = new SoapAdapter($this->config->create());
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $response = [];
        try {
            $response = $invoiceAdmin->activateInvoice($invoiceNo, $orderId);
        } catch (ResponseError $e) {
            // do something with the response error. E.g. logging
        }

        return $response;
    }
}
