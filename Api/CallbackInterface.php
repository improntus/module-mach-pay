<?php

namespace Improntus\MachPay\Api;


/**
 * Class CallbackInterface - Brief description of interface objective
 * @package Improntus\MachPay\Api
 */
interface CallbackInterface
{
    /**
     * @param string[] $data
     * @return mixed
     *@throws \Exception
     */
    public function updateStatus(array $data);
}
