<?php

namespace Webbhuset\CollectorBankCheckout\Gateway\Command;

use CollectorBank\PaymentSDK\Errors\ResponseError as ResponseError;
use Magento\Payment\Gateway\CommandInterface as CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\TransactionInterface;

class CollectorBankCommand implements CommandInterface
{
    protected $method;
    protected $paymentHandler;
    protected $invoice;
    protected $transaction;
    protected $logger;

    public function __construct(
        $client,
        \Webbhuset\CollectorBankCheckout\Data\PaymentHandlerFactory $paymentHandler,
        \Webbhuset\CollectorBankCheckout\Invoice\Administration $invoice,
        \Webbhuset\CollectorBankCheckout\Invoice\Transaction\ManagerFactory $transaction,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger
    ) {
        $this->method         = $client['method'];
        $this->paymentHandler = $paymentHandler;
        $this->invoice        = $invoice;
        $this->transaction    = $transaction;
        $this->logger         = $logger;
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
        $payment = $this->extractPayment($data);

        $paymentHandler = $this->paymentHandler->create();

        try {
            $invoiceNo = $paymentHandler->getPurchaseIdentifier($payment);
            $orderId = $payment->getOrder()->getId();

            $response = $this->invoice->activateInvoice(
                $invoiceNo,
                $orderId
            );
        } catch (ResponseError $e) {
            $incrementOrderId = (int)$payment->getOrder()->getIncrementOrderId();
            $this->logger->addCritical(
                "Response error on capture. increment orderId: {$incrementOrderId} invoiceNo {$invoiceNo}" .
                $e->getMessage()
            );

            return false;
        }

        $this->transaction->create()->addTransaction(
            $payment->getOrder(),
            TransactionInterface::TYPE_CAPTURE
        );

        return true;
    }

    public function authorize($data)
    {
        $payment = $this->extractPayment($data);

        $this->transaction->create()->addTransaction(
            $payment->getOrder(),
            TransactionInterface::TYPE_AUTH
        );

        return $data;
    }

    public function refund($payment)
    {
        $payment = $this->extractPayment($payment);
        $paymentHandler = $this->paymentHandler->create();

        try {
            $invoiceNo = $paymentHandler->getPurchaseIdentifier($payment);
            $orderId = (int)$payment->getOrder()->getId();

            $this->invoice->creditInvoice(
                $invoiceNo,
                $orderId
            );
        } catch (ResponseError $e) {
            $incrementOrderId = (int)$payment->getOrder()->getIncrementOrderId();
            $this->logger->addCritical(
                "Response error on refund increment orderId: {$incrementOrderId} invoiceNo {$invoiceNo}" .
                $e->getMessage()
            );

            return false;
        }
        $this->transaction->create()->addTransaction(
            $payment->getOrder(),
            TransactionInterface::TYPE_REFUND,
            true
        );

        return true;
    }

    public function void($payment)
    {
        $payment = $this->extractPayment($payment);
        $paymentHandler = $this->paymentHandler->create();

        $response = [];
        try {
            $invoiceNo = $paymentHandler->getPurchaseIdentifier($payment);
            $orderId = (int)$payment->getOrder()->getId();

            $response = $this->invoice->cancelInvoice(
                $invoiceNo,
                $orderId
            );
        } catch (ResponseError $e) {
            $incrementOrderId = (int)$payment->getOrder()->getIncrementOrderId();
            $this->logger->addCritical(
                "Response error on void / cancel increment orderId: {$incrementOrderId} invoiceNo; {$invoiceNo}" .
                $e->getMessage()
            );
            return false;
        }

        $this->transaction->create()->addTransaction(
            $payment->getOrder(),
            TransactionInterface::TYPE_VOID,
            true
        );

        return true;
    }

    public function cancel($payment)
    {
        return $this->void($payment);
    }

    public function extractPayment($payment)
    {
        $payment = SubjectReader::readPayment($payment);

        return $payment->getPayment();
    }
}
