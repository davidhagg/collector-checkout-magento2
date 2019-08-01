<?php

namespace Webbhuset\CollectorBankCheckout\Setup;

class InstallData implements \Magento\Framework\Setup\InstallDataInterface
{
    protected $statusFactory;
    protected $statusResourceFactory;

    public function __construct(
        \Magento\Sales\Model\Order\StatusFactory $statusFactory,
        \Magento\Sales\Model\ResourceModel\Order\StatusFactory $statusResourceFactory
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }

    public function install(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $status = $this->statusFactory->create();
        $statusResourceFactory = $this->statusResourceFactory->create();

        $orderStatuses = [];
        $orderStatuses[\Magento\Sales\Model\Order::STATE_NEW] = [
            'status' => 'collectorbank_new',
            'label' => 'Collector Bank - New'
        ];
        $orderStatuses[\Magento\Sales\Model\Order::STATE_PROCESSING] = [
            'status' => 'collectorbank_acknowledged',
            'label' => 'Collector Bank - Acknowledged'
        ];
        $orderStatuses[\Magento\Sales\Model\Order::STATE_HOLDED] = [
            'status' => 'collectorbank_onhold',
            'label' => 'Collector Bank - On Hold'
        ];
        $orderStatuses[\Magento\Sales\Model\Order::STATE_CANCELED] = [
            'status' => 'collectorbank_canceled',
            'label' => 'Collector Bank - Cancelled'
        ];

        foreach ($orderStatuses as $state => $orderStatus) {
            $status->setData($orderStatus);
            try {
                $statusResourceFactory->save($status);
            } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                continue;
            }
            $status->assignState($state, false, true);
        }
        $setup->endSetup();
    }
}