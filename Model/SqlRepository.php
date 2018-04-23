<?php

namespace Trezo\PagueVeloz\Model;

use Trezo\PagueVeloz\Model\ResourceModel\Sql\CollectionFactory as SqlCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Trezo\PagueVeloz\Api\SqlRepositoryInterface;
use Trezo\PagueVeloz\Model\ResourceModel\Sql as ResourceSql;
use Magento\Framework\Exception\CouldNotDeleteException;
use Trezo\PagueVeloz\Api\Data\SqlInterfaceFactory;
use Trezo\PagueVeloz\Api\Data\SqlSearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Store\Model\StoreManagerInterface;

class SqlRepository implements SqlRepositoryInterface
{

    protected $dataSqlFactory;

    private $storeManager;

    protected $SqlFactory;

    protected $SqlCollectionFactory;

    protected $resource;

    protected $searchResultsFactory;

    protected $dataObjectProcessor;

    protected $dataObjectHelper;


    /**
     * @param ResourceSql $resource
     * @param SqlFactory $sqlFactory
     * @param SqlInterfaceFactory $dataSqlFactory
     * @param SqlCollectionFactory $sqlCollectionFactory
     * @param SqlSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceSql $resource,
        SqlFactory $sqlFactory,
        SqlInterfaceFactory $dataSqlFactory,
        SqlCollectionFactory $sqlCollectionFactory,
        SqlSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->sqlFactory = $sqlFactory;
        $this->sqlCollectionFactory = $sqlCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataSqlFactory = $dataSqlFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Trezo\PagueVeloz\Api\Data\SqlInterface $sql
    ) {
        /* if (empty($sql->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $sql->setStoreId($storeId);
        } */
        try {
            $this->resource->save($sql);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the sql: %1',
                $exception->getMessage()
            ));
        }
        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($sqlId)
    {
        $sql = $this->sqlFactory->create();
        $sql->load($sqlId);
        if (!$sql->getId()) {
            throw new NoSuchEntityException(__('Sql with id "%1" does not exist.', $sqlId));
        }
        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->sqlCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'store_id') {
                    $collection->addStoreFilter($filter->getValue(), false);
                    continue;
                }
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $items = [];

        foreach ($collection as $sqlModel) {
            $sqlData = $this->dataSqlFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $sqlData,
                $sqlModel->getData(),
                'Trezo\PagueVeloz\Api\Data\SqlInterface'
            );
            $items[] = $this->dataObjectProcessor->buildOutputDataArray(
                $sqlData,
                'Trezo\PagueVeloz\Api\Data\SqlInterface'
            );
        }
        $searchResults->setItems($items);
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Trezo\PagueVeloz\Api\Data\SqlInterface $sql
    ) {
        try {
            $this->resource->delete($sql);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Sql: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($sqlId)
    {
        return $this->delete($this->getById($sqlId));
    }
}
