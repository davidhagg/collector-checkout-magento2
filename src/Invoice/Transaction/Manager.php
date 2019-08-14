<?php

namespace Webbhuset\CollectorBankCheckout\Invoice\Transaction;

class Manager
{
    public function __construct()
    {
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
}
