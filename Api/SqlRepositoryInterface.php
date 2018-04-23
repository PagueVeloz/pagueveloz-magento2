<?php

namespace Trezo\PagueVeloz\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface SqlRepositoryInterface
{


    /**
     * Save Sql
     * @param \Trezo\PagueVeloz\Api\Data\SqlInterface $sql
     * @return \Trezo\PagueVeloz\Api\Data\SqlInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Trezo\PagueVeloz\Api\Data\SqlInterface $sql
    );

    /**
     * Retrieve Sql
     * @param string $sqlId
     * @return \Trezo\PagueVeloz\Api\Data\SqlInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($sqlId);

    /**
     * Retrieve Sql matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Trezo\PagueVeloz\Api\Data\SqlSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Sql
     * @param \Trezo\PagueVeloz\Api\Data\SqlInterface $sql
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Trezo\PagueVeloz\Api\Data\SqlInterface $sql
    );

    /**
     * Delete Sql by ID
     * @param string $sqlId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($sqlId);
}
