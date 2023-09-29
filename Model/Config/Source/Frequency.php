<?php

namespace Improntus\MachPay\Model\Config\Source;

/**
 * Class Frequency - Brief description of class objective
 * @package Improntus\MachPay\Model\Config\Source
 */
class Frequency implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    protected static $_options;

    public const CRON_FRECUENTLY = 'F';

    public const CRON_DAILY = 'D';

    public const CRON_WEEKLY = 'W';

    public const CRON_MONTHLY = 'M';

    /**
     *
     * @return array|array[]
     */
    public function toOptionArray()
    {
        if (!self::$_options) {
            self::$_options = [
                ['label' => __('Frecuently'), 'value' => self::CRON_FRECUENTLY],
                ['label' => __('Daily'), 'value' => self::CRON_DAILY],
                ['label' => __('Weekly'), 'value' => self::CRON_WEEKLY],
                ['label' => __('Monthly'), 'value' => self::CRON_MONTHLY],
            ];
        }
        return self::$_options;
    }
}
