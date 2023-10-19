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
     * @param string $eventName
     * @param string $eventResourceId
     * @param string $eventUpstreamId
     * @return mixed
     * @throws \Exception
     */
    public function updateStatus(string $eventName, string $eventResourceId, string $eventUpstreamId);
}
