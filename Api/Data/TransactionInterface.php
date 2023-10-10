<?php

namespace Improntus\MachPay\Api\Data;

/**
 * Class TransactionInterface - get and set transaction fields
 * @package Improntus\MachPay\Api\Data
 */
interface TransactionInterface
{
    const TRANSACTION_ID = 'entity_id';
    const ORDER_ID = 'order_id';
    const MACHPAY_TRANSACTION_ID = 'transaction_id';
    const STATUS = 'status';
    const CREATED_AT = 'created_at';
    const EXPIRED_AT = 'expired_at';

    /**
     * Get transaction_id
     * @return string|null
     */
    public function getTransactionId();

    /**
     * Set transaction_id
     * @param string $transactionId
     * @return \Improntus\MachPay\Api\Data\TransactionInterface
     */
    public function setTransactionId($transactionId);

    /**
     * @return int|string|null
     */
    public function getOrderId();

    /**
     * @param $orderId
     * @return mixed
     */
    public function setOrderId($orderId);

    /**
     * @return string
     */
    public function getMachPayTransactionId();

    /**
     * @param $machPayTransactionId
     * @return mixed
     */
    public function setMachPayTransactionId($machPayTransactionId);

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @param $status
     * @return mixed
     */
    public function setStatus($status);

    /**
     * @return mixed
     */
    public function getCreatedAt();

    /**
     * @param $createdAt
     * @return mixed
     */
    public function setCreatedAt($createdAt);

    /**
     * @return mixed
     */
    public function getExpiredAt();

    /**
     * @param $expiredAt
     * @return mixed
     */
    public function setExpiredAt($expiredAt);
}
