<?php

namespace Webbhuset\CollectorBankCheckout\Invoice\Transaction;

class Manager
{
    protected $transactionFactory;
    protected $invoiceService;

    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory
    ) {
        $this->invoiceService        = $invoiceService;
        $this->transactionFactory    = $transactionFactory;
    }

    public function addTransaction(
        \Magento\Sales\Api\Data\OrderInterface $order,
        $type,
        $status = false
    ) {
        $payment = $order->getPayment();

        $id            = $order->getIncrementId();
        $txnId         = "{$id}-{$type}";
        $parentTransId = $payment->getLastTransId();
        $payment->setTransactionId($txnId)
            ->setIsTransactionClosed($status)
            ->setTransactionAdditionalInfo(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $payment->getAdditionalInformation()
            );

        $transaction = $payment->addTransaction($type, null, true);

        if ($parentTransId) {
            $transaction->setParentTxnId($parentTransId);
        }
        $transaction->save();
        $payment->save();
    }

    public function addInvoiceTransaction(
        \Magento\Sales\Model\Order\Invoice $invoice
    ) {
        $transaction = $this->transactionFactory->create()
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        $transaction->save();
    }
}
