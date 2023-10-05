<?php

namespace Improntus\MachPay\Model\ResourceModel\Transaction;

use Improntus\MachPay\Model\ResourceModel\Transaction as ResourceModel;
use Improntus\MachPay\Model\Transaction as Model;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(
            \Improntus\MachPay\Model\Transaction::class,
            \Improntus\MachPay\Model\ResourceModel\Transaction::class
        );
    }
}
