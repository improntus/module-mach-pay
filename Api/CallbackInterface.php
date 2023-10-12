<?php

namespace Improntus\MachPay\Api;


/**
 * Interface CallbackInterface - Webhook interface
 * @package Improntus\MachPay\Api
 */
interface CallbackInterface
{
    /**
     * Update status of orders
     *
     * @param string[] $data
     * @return mixed
     *@throws \Exception
     */
    public function updateStatus(array $data);
}
