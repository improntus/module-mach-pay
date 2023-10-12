<?php

namespace Improntus\MachPay\Model\Api;

use Improntus\MachPay\Api\CallbackInterface;
use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\Machpay;
use Magento\Sales\Model\Order;

/**
 * Class Callback - Weebhook model of MachPay
 * @package Improntus\MachPay\Model\Api
 */
class Callback implements CallbackInterface
{
    private const CONCATENATOR = '~';
    public const COMPLETE = 'Pago completado';
    public const EXPIRED = 'Pago expirado';
    public const FAILED = 'Pago fallido';
    public const REVERT = 'Pago reversado';
    public const REFUND = 'Reembolso completado';

    /**
     * @var Machpay
     */
    private $machPay;
    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Data $helper
     * @param Machpay $machPay
     */
    public function __construct(
        Data $helper,
        Machpay $machPay
    ) {
        $this->machPay = $machPay;
        $this->helper = $helper;
    }

    /**
     * Update status orders
     *
     * @param array $data
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function updateStatus(array $data)
    {
        if (isset($data['event_name']) && isset($data['event_resource_id']) && isset($data['event_upstream_id'])) {
            if ($transaction = $this->machPay->checkIfExists($data['event_resource_id'])) {
                /** @var Order $order */
                $order = $this->machPay->getOrderByTransactionId($data['event_resource_id']);
                $transactionId = $transaction->getMachPayTransactionId();

                switch ($data['event_name']) {
                    case self::COMPLETE:
                        if ($this->machPay->invoice($order, $transactionId)) {
                            return true;
                        } else {
                            $response = new \Magento\Framework\Webapi\Exception(__('Order could not be invoiced.'));
                        }
                        break;
                    case self::EXPIRED || self::FAILED || self::REVERT:
                        $this->processCancel($order, Order::STATE_CANCELED);
                        return true;
                    case self::REFUND:
                        return true;
                    default:
                        $message = "Failed AUTH Webhook Request: \n";
                        foreach ($data as $key => $value) {
                            $message .= " {$key} => {$value} \n";
                        }
                        $message .= "<== End webhook request ==> \n";
                        $this->helper->log($message);

                        $response = new \Magento\Framework\Webapi\Exception(__('Authentication failed'));
                }
            } else {
                $response = new \Magento\Framework\Webapi\Exception(__('There was no transaction with requested Id.'));
            }
        } else {
            $response =  new \Magento\Framework\Webapi\Exception(__('Invalid request data.'));
        }
        throw $response;
    }

    /**
     * Cancel order
     *
     * @param Order $order
     * @param string $status
     * @return bool
     */
    private function processCancel(Order $order, string $status)
    {
        $status = strtolower($status);
        $message = (__('Order ' . $status . ' by MachPay.'));
        if ($this->machPay->cancelOrder($order, $message)) {
            return true;
        } else {
            return false;
        }
    }
}
