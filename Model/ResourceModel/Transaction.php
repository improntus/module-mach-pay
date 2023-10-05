<?php

namespace Improntus\MachPay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Transaction extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'machpay_transaction_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('machpay_transaction', 'entity_id');
    }
}
