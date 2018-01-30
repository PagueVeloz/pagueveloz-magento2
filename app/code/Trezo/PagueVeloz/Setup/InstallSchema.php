<?php

namespace Trezo\PagueVeloz\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\InstallSchemaInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        $table_trezo_sql = $setup->getConnection()->newTable($setup->getTable('trezo_pagueveloz_transactions'));

        $table_trezo_sql->addColumn(
            'sql_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array('identity' => true,'nullable' => false,'primary' => true,'unsigned' => true,),
            'Entity ID'
        );

        $table_trezo_sql->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'order_id'
        );

        $table_trezo_sql->addColumn(
            'amount',
            \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
            null,
            [],
            'amount'
        );

        $table_trezo_sql->addColumn(
            'number',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'number'
        );

        $table_trezo_sql->addColumn(
            'expiration',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
            null,
            [],
            'expiration'
        );

        $table_trezo_sql->addColumn(
            'submit_dc',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'submit_dc'
        );

        $table_trezo_sql->addColumn(
            'query_dc',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'query_dc'
        );

        $setup->getConnection()->createTable($table_trezo_sql);

        $setup->endSetup();
    }
}
