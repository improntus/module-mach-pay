<?php

namespace Improntus\MachPay\Model;

use Improntus\MachPay\Api\Data\TransactionInterface;
use Improntus\MachPay\Api\Data\TransactionInterfaceFactory;
use Improntus\MachPay\Api\Data\TransactionSearchResultsInterfaceFactory;
use Improntus\MachPay\Api\TransactionRepositoryInterface;
use Improntus\MachPay\Model\ResourceModel\Transaction as ResourceTransaction;
use Improntus\MachPay\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class TransactionRepository - Brief description of class objective
 * @package Improntus\MachPay\Model
 */
class TransactionRepository implements \Improntus\MachPay\Api\TransactionRepositoryInterface
{

    /**
     * @var ResourceTransaction
     */
    protected $resource;

    /**
     * @var TransactionInterfaceFactory
     */
    protected $transactionFactory;

    /**
     * @var TransactionCollectionFactory
     */
    protected $transactionCollectionFactory;

    /**
     * @var Transaction
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;


    /**
     * @param ResourceTransaction $resource
     * @param TransactionInterfaceFactory $transactionFactory
     * @param TransactionCollectionFactory $transactionCollectionFactory
     * @param TransactionSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceTransaction $resource,
        TransactionInterfaceFactory $transactionFactory,
        TransactionCollectionFactory $transactionCollectionFactory,
        TransactionSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->transactionFactory = $transactionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(TransactionInterface $transaction)
    {
        try {
            $this->resource->save($transaction);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the transaction: %1',
                $exception->getMessage()
            ));
        }
        return $transaction;
    }

    /**
     * @inheritDoc
     */
    public function get($transactionId)
    {
        $transaction = $this->transactionFactory->create();
        $this->resource->load($transaction, $transactionId, TransactionInterface::MACHPAY_TRANSACTION_ID);
        if (!$transaction->getId()) {
            throw new NoSuchEntityException(__('Transaction with id "%1" does not exist.', $transactionId));
        }
        return $transaction;
    }

    public function getByOrderId($orderId)
    {
        $transaction = $this->transactionFactory->create();
        $this->resource->load($transaction, $orderId, 'order_id');
        if (!$transaction->getId()) {
            return false;
        }
        return $transaction;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->transactionCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(TransactionInterface $transaction)
    {
        try {
            $transactionModel = $this->transactionFactory->create();
            $this->resource->load($transactionModel, $transaction->getTransactionId());
            $this->resource->delete($transactionModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Transaction: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($transactionId)
    {
        return $this->delete($this->get($transactionId));
    }
}
