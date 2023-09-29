<?php

namespace Improntus\MachPay\Model\Config\Backend;

use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class CronConfig - Brief description of class objective
 * @package Improntus\MachPay\Model\Config\Backend
 */
class CronConfig extends \Magento\Framework\App\Config\Value
{
    public const CRON_STRING_PATH = 'crontab/machpay/jobs/improntus_machpay_cancel_orders/schedule/cron_expr';
    public const CRON_MODEL_PATH = 'crontab/machpay/jobs/improntus_machpay_cancel_orders/run/model';

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;

    /**
     * @var string
     */
    protected $_runModelPath = '';

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param string $runModelPath
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $runModelPath = '',
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->_configValueFactory = $configValueFactory;
        $this->_runModelPath = $runModelPath;
    }

    /**
     * Get cron config
     *
     * @return CronConfig
     * @throws CouldNotSaveException
     */
    public function afterSave()
    {
        $frequency = $this->getData('groups/machpay/groups/cancel_orders/fields/schedule/value');
        $time = $this->getData('groups/machpay/groups/cancel_orders/fields/time/value');

        $cronExprArray = [
            $frequency == \Improntus\MachPay\Model\Config\Source\Frequency::CRON_FRECUENTLY ?
                "*/".(int)($time[1]) : (int)($time[1]),
            $frequency == \Improntus\MachPay\Model\Config\Source\Frequency::CRON_FRECUENTLY ? '*' : (int)($time[0]),
            $frequency == \Improntus\MachPay\Model\Config\Source\Frequency::CRON_MONTHLY ? '1' : '*',
            '*',
            $frequency == \Improntus\MachPay\Model\Config\Source\Frequency::CRON_WEEKLY ? '1' : '*',
        ];

        $cronExprString = join(' ', $cronExprArray);

        try {
            $this->_configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();
            $this->_configValueFactory->create()->load(
                self::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->_runModelPath
            )->setPath(
                self::CRON_MODEL_PATH
            )->save();
        } catch (CouldNotSaveException $e) {
             throw new CouldNotSaveException(__('We can\'t save the cron expression.'));
        }

        return parent::afterSave();
    }
}
