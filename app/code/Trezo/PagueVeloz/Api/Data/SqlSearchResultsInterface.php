<?php

namespace Trezo\PagueVeloz\Api\Data;

interface SqlSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Sql list.
     * @return \Trezo\PagueVeloz\Api\Data\SqlInterface[]
     */
    public function getItems();

    /**
     * Set order_id list.
     * @param \Trezo\PagueVeloz\Api\Data\SqlInterface[] $items
     * @return $this
     */

    public function setItems(array $items);
}
