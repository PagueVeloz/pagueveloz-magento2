<?php

namespace Trezo\PagueVeloz\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $installer = $setup;



        if (version_compare($context->getVersion(), "1.0.1", "<")) {
            // Insert statuses
            $installer->getConnection()->insertArray(
                'sales_order_status',
                array(
                    'status',
                    'label'
                ),
                array(
                    array('status' => 'approved', 'label' => 'Aprovado'),
                    array('status' => 'processingpreorder', 'label' => 'Recebido Pré-Venda')
                )
            );

            // Insert states and mapping of statuses to states
            $installer->getConnection()->insertArray(
                'sales_order_status_state',
                array(
                    'status',
                    'state',
                    'is_default'
                ),
                array(
                    array(
                        'status' => 'approved',
                        'state' => 'processing ',
                        'is_default' => 0
                    ),
                    array(
                        'status' => 'processingpreorder',
                        'state' => 'processing ',
                        'is_default' => 0
                    )
                )
            );
        }
        $setup->endSetup();
    }
}
