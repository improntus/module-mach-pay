<?php

namespace Improntus\MachPay\Api;

/**
 * Class TransactionRepositoryInterface - Repository interface of transactions
 * @package Improntus\MachPay\Api
 */
interface TransactionRepositoryInterface
{
    /**
     * Save Transaction
     *
     * @param \Improntus\MachPay\Api\Data\TransactionInterface $transaction
     * @return \Improntus\MachPay\Api\Data\TransactionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Improntus\MachPay\Api\Data\TransactionInterface $transaction
    );

    /**
     * Retrieve Transaction
     *
     * @param string $transactionId
     * @return \Improntus\MachPay\Api\Data\TransactionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($transactionId);

    /**
     * Retrieve Transaction
     *
     * @param int $orderId
     * @return \Improntus\MachPay\Api\Data\TransactionInterface | false
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByOrderId($orderId);

    /**
     * Retrieve Transaction matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Improntus\MachPay\Api\Data\TransactionSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Transaction
     *
     * @param \Improntus\MachPay\Api\Data\TransactionInterface $transaction
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Improntus\MachPay\Api\Data\TransactionInterface $transaction
    );

    /**
     * Delete Transaction by ID
     *
     * @param string $transactionId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($transactionId);
}
