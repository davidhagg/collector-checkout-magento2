<?php

namespace Webbhuset\CollectorBankCheckout\Gateway\Command;

use CollectorBank\PaymentSDK\Errors\ResponseError as ResponseError;
use Magento\Payment\Gateway\CommandInterface as CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class CollectorBankCommand implements CommandInterface
{
    protected $method;
    protected $paymentHandler;
    protected $invoice;

    public function __construct(
        $client,
        \Webbhuset\CollectorBankCheckout\Data\PaymentHandlerFactory $paymentHandler,
        \Webbhuset\CollectorBankCheckout\Invoice\Administration $invoice
    ) {
        $this->method         = $client['method'];
        $this->paymentHandler = $paymentHandler;
        $this->invoice        = $invoice;
    }

    public function execute(array $commandSubject)
    {
        $method = $this->method;
        if (method_exists($this, $method)) {
            call_user_func([$this, $method], $commandSubject);
        }
    }

    public function capture($payment)
    {
        $payment = SubjectReader::readPayment($payment);
        $payment = $payment->getPayment();
        $paymentHandler = $this->paymentHandler->create();

        $purchaseIdentifier = $paymentHandler->getPurchaseIdentifier($payment);

        try {
            $this->invoice->activateInvoice(
                $purchaseIdentifier,
                $payment->getOrder()->getId()
            );
        } catch (ResponseError $e) {
            // do something ... log logging and output something in admin?
        }

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
