<?php

namespace Improntus\MachPay\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger as MonologLogger;

/**
 * Class MachpayHandler - Handler Class
 * @package Improntus\MachPay\Logger\Handler
 */
class MachpayHandler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = MonologLogger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = 'var/log/machpay/debug.log';
}
