<?php

namespace Trezo\PagueVeloz\Model\ResourceModel\Sql;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Trezo\PagueVeloz\Model\Sql',
            'Trezo\PagueVeloz\Model\ResourceModel\Sql'
        );
    }
}
