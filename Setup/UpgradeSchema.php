<?php

namespace Superb\Recommend\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements  UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '0.0.11') < 0) {
            $connection = $installer->getConnection();
            $tableName = $connection->getTableName('superb_recommend_orders_queue');
            if ($connection->isTableExists($tableName) == true) {
                $connection->dropTable($tableName);
            }
            $table = $connection->newTable($tableName)
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'ID'
                )
                ->addColumn(
                    'email',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable => false'],
                    'Email'
                )
                ->addColumn(
                    'order_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable => false'],
                    'Order Id'
                )
                ->addColumn(
                    'cid',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable => true'],
                    'Customer Id'
                )
                ->addColumn(
                    'status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable => false'],
                    'Status'
                )
                ->addColumn(
                    'store_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable => true'],
                    'Store Id'
                );
            $connection->createTable($table);
        }
        if (version_compare($context->getVersion(), '0.0.20') < 0) {
            $connection = $installer->getConnection();
            $tableName = $connection->getTableName('superb_recommend_orders_queue');
            if ($connection->isTableExists($tableName) == true) {
                $connection->dropTable($tableName);
            }
            $table = $connection->newTable($tableName)
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'ID'
                )
                ->addColumn(
                    'email',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => false'],
                    'Email'
                )
                ->addColumn(
                    'order_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => false'],
                    'Order Id'
                )
                ->addColumn(
                    'cid',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => true'],
                    'Customer Id'
                )
                ->addColumn(
                    'status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => false'],
                    'Status'
                )
                ->addColumn(
                    'store_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => true'],
                    'Store Id'
                )
                ->addColumn(
                    'bid',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => true'],
                    'Browser Id'
                )
                ->addColumn(
                    'customer_name',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => true'],
                    'Customer Name'
                )
                ->addColumn(
                    'grand_total',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => false'],
                    'Grand Total'
                )
                ->addColumn(
                    'tax',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => false'],
                    'Tax'
                )
                ->addColumn(
                    'delivery',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => false'],
                    'Delivery'
                )
                ->addColumn(
                    'sale_qty',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => false'],
                    'Sale Qty'
                )
                ->addColumn(
                    'currency',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => false'],
                    'Currency'
                )
                ->addColumn(
                    'delivery',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => false'],
                    'Currency'
                )
                ->addColumn(
                    'products',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    65536,
                    ['nullable => true'],
                    'Products'
                )
                ->addColumn(
                    'segment',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    ['nullable => true'],
                    'Segment'
                );
            $connection->createTable($table);
        }
        $installer->endSetup();
    }
}
