<?php

namespace Webbhuset\CollectorBankCheckout\Invoice;

use CollectorBank\PaymentSDK\Adapter\SoapAdapter;
use CollectorBank\PaymentSDK\Invoice\Administration as InvoiceAdministration;

class Administration
{
    protected $config;
    protected $invoiceService;
    protected $transaction;
    protected $logger;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Webbhuset\CollectorBankCheckout\Invoice\Transaction\ManagerFactory $transaction,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger
    ) {
        $this->config         = $config;
        $this->invoiceService = $invoiceService;
        $this->transaction    = $transaction;
        $this->logger         = $logger;
    }

    public function activateInvoice(string $invoiceNo, string $orderId):array
    {
        $adapter = new SoapAdapter($this->config->create());
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice activated online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->activateInvoice($invoiceNo, $orderId);
    }

    public function cancelInvoice(string $invoiceNo, string $orderId):array
    {
        $adapter = new SoapAdapter($this->config->create());
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice cancelled online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->cancelInvoice($invoiceNo, $orderId);
    }

    public function creditInvoice(string $invoiceNo, string $orderId):array
    {
        $adapter = new SoapAdapter($this->config->create());
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice credited online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->creditInvoice($invoiceNo, $orderId);
    }

    public function getInvoiceInformation(int $invoiceNo, int $orderId, string $clientIp):array
    {
        $adapter = new SoapAdapter($this->config->create());
        $invoiceAdmin = new InvoiceAdministration($adapter);

        return $invoiceAdmin->getInvoiceInformation($invoiceNo, $clientIp, $orderId);
    }

    public function invoiceOrderOffline(
        \Magento\Sales\Model\Order $order
    ) {
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $this->logger->addInfo(
            "Invoice order offline orderId: {$order->getIncrementId()} qouteId: {$order->getQuoteId()} "
        );

        $this->transaction->create()->addInvoiceTransaction($invoice);
    }
}
