<?php

namespace Improntus\MachPay\Plugin\Sales\Block\Adminhtml\Order;

use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\Machpay;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

/**
 * Class View - Add button create refund in machpay
 * @package Improntus\MachPay\Plugin\Sales\Block\Adminhtml\Order
 */
class View
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Data
     */
    private Data $helper;
    private Machpay $machpay;

    /**
     * @param UrlInterface $urlBuilder
     * @param Data $helper
     * @param Machpay $machpay
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Data $helper,
        Machpay $machpay
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
        $this->machpay = $machpay;
    }

    /**
     * Add button create refund to machpay
     *
     * @param OrderView $subject
     * @return void
     * @throws LocalizedException
     */
    public function beforeSetLayout(OrderView $subject)
    {
        $message = __('¿Are you sure you want to refund the invoice in MachPay?');

        $order = $subject->getOrder();
        $refundAvailable = $this->helper->isRefundAvailable();
        $url = $this->urlBuilder->getUrl(
            'machpay/order/refund',
            [
                'order_id' => $order->getId(),
            ]
        );
        if ($refundAvailable && $order->canCreditmemo() && $this->machpay->validateTransactionCreation($order->getId())) {
            $subject->addButton(
                'machpay_refund',
                [
                    'label' => __('Create Refund Machpay'),
                    'class' => __('refund-machpay'),
                    'id' => 'create-refund-machpay',
                    'onclick' => "confirmSetLocation('{$message}', '{$url}')",
                ]
            );
        }
    }
}
