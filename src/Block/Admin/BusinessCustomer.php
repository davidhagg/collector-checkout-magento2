<?php

namespace Webbhuset\CollectorBankCheckout\Block\Admin;

use Webbhuset\CollectorBankCheckout\Config\Source\Customer\DefaultType as CustomerType;

class BusinessCustomer extends \Magento\Backend\Block\Template
{
    protected $orderHandler;
    protected $request;
    protected $orderRepository;
    protected $order;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = [],
        \Webbhuset\CollectorBankCheckout\Data\OrderHandler $orderHandler,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\OrderRepository $orderRepository
    ) {
        parent::__construct($context, $data);

        $this->orderHandler    = $orderHandler;
        $this->request         = $request;
        $this->orderRepository = $orderRepository;
    }

    public function init()
    {
        $this->order = $this->loadOrder();
    }

    public function getReference()
    {
        return $this->orderHandler->getReference($this->order);
    }

    public function isBusinessCustomer()
    {
        $customerType = $this->orderHandler->getCustomerType($this->order);

        return CustomerType::BUSINESS_CUSTOMERS == $customerType;
    }

    public function getOrgNumber()
    {
        return $this->orderHandler->getOrgNumber($this->order);
    }

    private function loadOrder()
    {
        $orderId = $this->request->getParam('order_id');

        return $this->orderRepository->get($orderId);
    }
}
