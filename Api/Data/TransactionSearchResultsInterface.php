<?php

namespace Improntus\MachPay\Api\Data;

/**
 * Class TransactionSearchResultsInterface - get and set transaction list
 * @package Improntus\MachPay\Api\Data
 */
interface TransactionSearchResultsInterface
{
    /**
     * Get MachPay Transaction list.
     * @return \Improntus\MachPay\Api\Data\TransactionInterface[]
     */
    public function getItems();

    /**
     * Set MachPay Transaction list.
     * @param \Improntus\MachPay\Api\Data\TransactionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
