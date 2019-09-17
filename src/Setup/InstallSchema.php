<?php

namespace Webbhuset\CollectorBankCheckout\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     *
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->addCollectorBankQuoteColumns($setup);
        $this->addCollectorBankSalesOrderColumns($setup);

        $setup->endSetup();
    }

    protected function addCollectorBankQuoteColumns(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $table = $setup->getTable('quote');

        $columns = [
            'collectorbank_private_id' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Collector Bank private id',
            ],
            'collectorbank_public_id' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Collector Bank public id',
            ],
            'collectorbank_store_id' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Collector Bank store id',
            ]
        ];
        $this->addColumns($columns, $table, $setup);
    }

    private function addCollectorBankSalesOrderColumns(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $table = $setup->getTable('sales_order');

        $columns = [
            'collectorbank_private_id' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Collector Bank private id',
            ],
            'collectorbank_public_id' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Collector Bank public id',
            ],
            'collectorbank_store_id' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => true,
                'comment' => 'Collector Bank store id',
            ]
        ];
        $this->addColumns($columns, $table, $setup);
    }

    private function addColumns($columns, $table, \Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        foreach ($columns as $name => $definition) {
            $connection->addColumn($table, $name, $definition);
        }
    }
}
