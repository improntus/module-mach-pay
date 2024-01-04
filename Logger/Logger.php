<?php

namespace Improntus\MachPay\Logger;

/**
 * Class Logger - Set logger name
 * @package Improntus\MachPay\Logger
 */
class Logger extends \Monolog\Logger
{
    public function setName($name)
    {
        $this->name = $name;
    }
}
