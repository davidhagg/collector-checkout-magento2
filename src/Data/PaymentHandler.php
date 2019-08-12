<?php

namespace Webbhuset\CollectorBankCheckout\Data;

class PaymentHandler
{
    public function getMethodTitle(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "method_title");
    }

    public function getPaymentName(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "payment_name");
    }

    public function getAmountToPay(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "amount_to_pay");
    }

    public function getInvoiceDeliveryMethod(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "invoice_delivery_method");
    }

    public function getPurchaseIdentifier(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "purchase_identifier");
    }

    public function getResult(
        \Magento\Payment\Model\InfoInterface $payment
    ) {
        $info = $payment->getAdditionalInformation();

        return $this->extractValue($info, "result");
    }

    private function extractValue($array, $key)
    {
        if (!isset($array[$key])) {

            return "";
        }

        return $array[$key];
    }
}
