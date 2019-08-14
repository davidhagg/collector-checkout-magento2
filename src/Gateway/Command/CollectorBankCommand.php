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

    public function __construct(
        $client,
        \Webbhuset\CollectorBankCheckout\Data\PaymentHandlerFactory $paymentHandler,
        \Webbhuset\CollectorBankCheckout\Invoice\Administration $invoice,
        \Webbhuset\CollectorBankCheckout\Invoice\Transaction\ManagerFactory $transaction
    ) {
        $this->method         = $client['method'];
        $this->paymentHandler = $paymentHandler;
        $this->invoice        = $invoice;
        $this->transaction    = $transaction;
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
            $response = $this->invoice->activateInvoice(
                $paymentHandler->getPurchaseIdentifier($payment),
                $payment->getOrder()->getId()
            );
        } catch (ResponseError $e) {
            // do something ... logging and output something in admin?

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
            $this->invoice->creditInvoice(
                $paymentHandler->getPurchaseIdentifier($payment),
                (int)$payment->getOrder()->getId()
            );
        } catch (ResponseError $e) {

            return false;
            // do something ... logging and output something in admin?
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
            $response = $this->invoice->cancelInvoice(
                $paymentHandler->getPurchaseIdentifier($payment),
                $payment->getOrder()->getId()
            );
        } catch (ResponseError $e) {

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
