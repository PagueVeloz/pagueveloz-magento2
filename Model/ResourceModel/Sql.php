<?php

namespace Trezo\PagueVeloz\Model\ResourceModel;

class Sql extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('trezo_pagueveloz_transactions', 'sql_id');
    }
}
