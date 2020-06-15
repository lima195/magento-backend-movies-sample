<?php

namespace Lima\Movie\Model;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\NoSuchEntityException;
use Lima\Movie\Api\Data\QueueSearchResultsInterfaceFactory;
use Lima\Movie\Api\QueueRepositoryInterface;
use Lima\Movie\Model\ResourceModel\Queue as QueueResource;
use Lima\Movie\Model\ResourceModel\Queue\CollectionFactory;

/**
 * Class QueueRepository
 * @package Lima\Movie\Model
 */
class QueueRepository implements QueueRepositoryInterface
{
    /**
     * @var QueueResource
     */
    private $queueResource;

    /**
     * @var QueueFactory
     */
    private $queueFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var QueueSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * QueueRepository constructor.
     * @param QueueResource $queueResource
     * @param QueueFactory $queueFactory
     * @param CollectionFactory $collectionFactory
     * @param QueueSearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        QueueResource $queueResource,
        QueueFactory $queueFactory,
        CollectionFactory $collectionFactory,
        QueueSearchResultsInterfaceFactory $searchResultsFactory
    )
    {
        $this->queueResource = $queueResource;
        $this->queueFactory = $queueFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @param \Lima\Movie\Api\Data\QueueInterface $queue
     * @return mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(\Lima\Movie\Api\Data\QueueInterface $queue)
    {
        $this->queueResource->save($queue);
        return $queue->getImportId();
    }

    /**
     * @param $queueId
     * @return Queue|mixed
     * @throws NoSuchEntityException
     */
    public function getById($queueId)
    {
        $queue = $this->queueFactory->create();
        $this->queueResource->load($queue, $queueId);
        if(!$queue->getImportId()) {
            throw new NoSuchEntityException('Queue does not exist');
        }
        return $queue;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Lima\Movie\Api\Data\QueueSearchResultsInterface|mixed
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }
        /** @var Magento\Framework\Api\SortOrder $sortOrder */
        foreach ((array)$searchCriteria->getSortOrders() as $sortOrder) {
            $field = $sortOrder->getField();
            $collection->addOrder(
                $field,
                $this->getDirection($sortOrder->getDirection())
            );

        }

        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->load();
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setCriteria($searchCriteria);

        $queues=[];
        foreach ($collection as $queue){
            $queues[] = $queue;
        }
        $searchResults->setItems($queues);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @param \Lima\Movie\Api\Data\QueueInterface $queueId
     * @return bool|mixed
     * @throws \Exception
     */
    public function delete($queueId)
    {
        $queue = $this->queueFactory->create();
        $queue->setId($queueId);
        if( $this->queueResource->delete($queue)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $direction
     * @return bool|string
     */
    private function getDirection($direction)
    {
        return $direction == SortOrder::SORT_ASC ?: SortOrder::SORT_DESC;
    }

    /**
     * @param $group
     * @param $collection
     */
    private function addFilterGroupToCollection($group, $collection)
    {
        $fields = [];
        $conditions = [];

        foreach($group->getFilters() as $filter){
            $condition = $filter->getConditionType() ?: 'eq';
            $field = $filter->getField();
            $value = $filter->getValue();
            $fields[] = $field;
            $conditions[] = [$condition=>$value];

        }
        $collection->addFieldToFilter($fields, $conditions);
    }
}
