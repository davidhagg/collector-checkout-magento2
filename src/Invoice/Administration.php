<?php

namespace Webbhuset\CollectorBankCheckout\Invoice;

use CollectorBank\PaymentSDK\Adapter\SoapAdapter;
use CollectorBank\PaymentSDK\Invoice\Administration as InvoiceAdministration;

/**
 * Class Administration
 *
 * @package Webbhuset\CollectorBankCheckout\Invoice
 */
class Administration
{
    /**
     * @var \Webbhuset\CollectorBankCheckout\Config\ConfigFactory
     */
    protected $config;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;
    /**
     * @var Transaction\ManagerFactory
     */
    protected $transaction;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Data\OrderHandler
     */
    protected $orderHandler;

    /**
     * Administration constructor.
     *
     * @param \Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config
     * @param \Magento\Sales\Model\Service\InvoiceService           $invoiceService
     * @param Transaction\ManagerFactory                            $transaction
     * @param \Magento\Sales\Model\OrderRepository                  $orderRepository
     * @param \Webbhuset\CollectorBankCheckout\Data\OrderHandler    $orderHandler
     * @param \Webbhuset\CollectorBankCheckout\Logger\Logger        $logger
     */
    public function __construct(
        \Webbhuset\CollectorBankCheckout\Config\ConfigFactory $config,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Webbhuset\CollectorBankCheckout\Invoice\Transaction\ManagerFactory $transaction,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Webbhuset\CollectorBankCheckout\Data\OrderHandler $orderHandler,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger
    ) {
        $this->config          = $config;
        $this->invoiceService  = $invoiceService;
        $this->transaction     = $transaction;
        $this->logger          = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderHandler    = $orderHandler;
    }

    /**
     * Activate the invoice in collector bank portal
     *
     * @param string $invoiceNo
     * @param string $orderId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function activateInvoice(string $invoiceNo, string $orderId):array
    {
        $config = $this->config->create();
        $config = $this->setStoreIdOnConfig($config, $orderId);

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice activated online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->activateInvoice($invoiceNo, $orderId);
    }

    /**
     * Activate the invoice in collector bank portal
     *
     * @param string $invoiceNo
     * @param string $orderId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function cancelInvoice(string $invoiceNo, string $orderId):array
    {
        $config = $this->config->create();
        $config = $this->setStoreIdOnConfig($config, $orderId);

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice cancelled online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->cancelInvoice($invoiceNo, $orderId);
    }

    /**
     * Credit an invoice in collector bank portal
     *
     * @param string $invoiceNo
     * @param string $orderId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function creditInvoice(string $invoiceNo, string $orderId):array
    {
        $config = $this->config->create();
        $config = $this->setStoreIdOnConfig($config, $orderId);

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        $this->logger->addInfo(
            "Invoice credited online orderId: {$orderId} invoiceNo: {$invoiceNo} "
        );

        return $invoiceAdmin->creditInvoice($invoiceNo, $orderId);
    }

    /**
     * Get invoice information from collector bank portal
     *
     * @param int    $invoiceNo
     * @param int    $orderId
     * @param string $clientIp
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getInvoiceInformation(int $invoiceNo, int $orderId, string $clientIp):array
    {
        $config = $this->config->create();
        $config = $this->setStoreIdOnConfig($config, $orderId);

        $adapter = new SoapAdapter($config);
        $invoiceAdmin = new InvoiceAdministration($adapter);

        return $invoiceAdmin->getInvoiceInformation($invoiceNo, $clientIp, $orderId);
    }

    /**
     * Invoice an order offline
     *
     * @param \Magento\Sales\Model\Order $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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

    /**
     * Set order id on the config object
     *
     * @param \Webbhuset\CollectorBankCheckout\Config\Config $config
     * @param string                                         $orderId
     * @return \Webbhuset\CollectorBankCheckout\Config\Config
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setStoreIdOnConfig(
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        string $orderId
    ) {
        $order = $this->orderRepository->get($orderId);
        $storeId = $this->orderHandler->getStoreId($order);
        $config->setStoreId($storeId);
        return $config;
    }
}
