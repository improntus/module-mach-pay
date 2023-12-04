<?php

namespace Improntus\MachPay\Model\Api;

use Improntus\MachPay\Api\CallbackInterface;
use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\Machpay;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Exception;
use Magento\Sales\Model\Order;

/**
 * Class Callback - Weebhook model of MachPay
 * @package Improntus\MachPay\Model\Api
 */
class Callback implements CallbackInterface
{
    public const COMPLETE = 'business-payment-completed';
    public const EXPIRED = 'business-payment-expired';
    public const FAILED = 'business-payment-failed';
    public const REVERT = 'business-payment-reversed';
    public const REFUND = 'business-refund-completed';

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
     * @param string $eventName
     * @param string $eventResourceId
     * @param string $eventUpstreamId
     * @return array|false
     * @throws Exception
     * @throws LocalizedException
     */
    public function updateStatus(string $eventName, string $eventResourceId, string $eventUpstreamId)
    {
        if ($eventName && $eventResourceId && $eventUpstreamId) {
            if ($transaction = $this->machPay->checkIfExists($eventResourceId)) {
                /** @var Order $order */
                $order = $this->machPay->getOrderByTransactionId($eventResourceId);
                $transactionId = $transaction->getMachPayTransactionId();

                switch ($eventName) {
                    case self::COMPLETE:
                        if ($this->machPay->invoice($order, $transactionId)) {
                            return true;
                        } else {
                            $response = new Exception(__('Order could not be invoiced.'));
                        }
                        break;
                    case self::EXPIRED || self::FAILED || self::REVERT:
                        $this->processCancel($order, Order::STATE_CANCELED);
                        return true;
                    case self::REFUND:
                        if ($this->machPay->refund($order, $transactionId)) {
                            return true;
                        } else {
                            $response = new Exception(__('Order could not be refunded.'));
                        }
                        break;
                    default:
                        $message = "Failed AUTH Webhook Request: \n";
                        $message .= $eventName . " " . $eventResourceId . " " . $eventUpstreamId;
                        $message .= "<== End webhook request ==> \n";
                        $this->helper->log($message);

                        $response = new Exception(__('Authentication failed'));
                }
            } else {
                $response = new Exception(__('There was no transaction with requested Id.'));
            }
        } else {
            $response =  new Exception(__('Invalid request data.'));
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
        $message = (__('Order %1 by MachPay.', $status));
        if ($this->machPay->cancelOrder($order, $message)) {
            return true;
        } else {
            return false;
        }
    }
}
