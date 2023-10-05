<?php

namespace Improntus\MachPay\Model\Api;

use Improntus\MachPay\Api\CallbackInterface;
use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\Machpay;

/**
 * Class Callback - Brief description of class objective
 * @package Improntus\MachPay\Model\Api
 */
class Callback implements CallbackInterface
{
    private const CONCATENATOR = '~';

    /**
     * @var Machpay
     */
    private $machPay;
    /**
     * @var Data
     */
    private $helper;

    public function __construct(
        Data $helper,
        Machpay $machPay
    ) {
        $this->machPay = $machPay;
        $this->helper = $helper;
    }

    /**
     * @inheritDoc
     */
    public function updateStatus(array $data)
    {
        if (
            isset($data['id']) &&
            isset($data['status']) &&
            isset($data['expired_at']) &&
            isset($data['created_at']) &&
            isset($data['signature'])
        ) {
            if ($transaction = $this->machPay->checkIfExists($data['id'])) {
                $order = $this->machPay->getOrderByTransactionId($data['id']);
                $transactionId = $transaction->getMachPayTransactionId();
                $transactionCreatedAt = $data['created_at'];
                $unhashedSignature =
                    $this->helper->getSecret($order->getStoreId()) .
                    $this::CONCATENATOR .
                    $transactionId .
                    $this::CONCATENATOR .
                    $transactionCreatedAt;

                $signature = hash('sha256', $unhashedSignature);
                if ($signature === $data['signature']) {
                    if (strtolower($data['status']) == 'processed') {
                        if ($this->machPay->invoice($order, $data['id'])) {
                            return true;
                        } else {
                            $response = new \Magento\Framework\Webapi\Exception(__('Order could not be invoiced.'));
                        }
                    } else {
                        return $this->processCancel($order, $data['status']);
                    }
                } else {
                    $message = "Failed AUTH Webhook Request: \n";
                    foreach ($data as $key => $value) {
                        $message .= "   {$key} => {$value} \n";
                    }
                    $message .= "<== End webhook request ==> \n";
                    $message .= "Local signature: {$signature} \n";
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
     * @param $order
     * @param $status
     * @return bool
     */
    private function processCancel($order, $status)
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
