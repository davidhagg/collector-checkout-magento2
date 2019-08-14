<?php

namespace Webbhuset\CollectorBankCheckout\Invoice;

use CollectorBank\PaymentSDK\Adapter\SoapAdapter;
use CollectorBank\PaymentSDK\Invoice\Administration as InvoiceAdministration;

class Administration
{
    protected $config;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config
    ) {
        $this->config = $config;
    }

    public function activateInvoice(string $invoiceNo, string $orderId):array
    {
        $adapter = new SoapAdapter($this->config->create());
        $invoiceAdmin = new InvoiceAdministration($adapter);

        return $invoiceAdmin->activateInvoice($invoiceNo, $orderId);
    }

    public function cancelInvoice(string $invoiceNo, string $orderId):array
    {
        $adapter = new SoapAdapter($this->config->create());
        $invoiceAdmin = new InvoiceAdministration($adapter);

        return $invoiceAdmin->cancelInvoice($invoiceNo, $orderId);
    }

    public function creditInvoice(string $invoiceNo, string $orderId):array
    {
        $adapter = new SoapAdapter($this->config->create());
        $invoiceAdmin = new InvoiceAdministration($adapter);

        return $invoiceAdmin->creditInvoice($invoiceNo, $orderId);
    }

    public function getInvoiceInformation(int $invoiceNo, int $orderId, string $clientIp):array
    {
        $adapter = new SoapAdapter($this->config->create());
        $invoiceAdmin = new InvoiceAdministration($adapter);

        return $invoiceAdmin->getInvoiceInformation($invoiceNo, $clientIp, $orderId);
    }
}
