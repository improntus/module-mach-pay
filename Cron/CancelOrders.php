<?php

namespace Improntus\MachPay\Cron;

use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\Machpay;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class CancelOrders
{
    protected const PENDING = 'pending';
    protected const PAYMENT_METHOD = 'machpay';

    private OrderRepositoryInterface $orderRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private Data $helper;
    private Machpay $machpay;

    /**
     * CancelOrdersPending constructor
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Data $helper
     * @param Machpay $machpay
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Data $helper,
        Machpay $machpay
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper = $helper;
        $this->machpay = $machpay;
    }

    /**
     * Cronjob Description
     *
     * @return void
     * @throws \Exception
     */
    public function execute(): void
    {
        if (!$this->helper->isCronEnabled()) {
            $this->helper->log(
                __('Cancel orders Machpay is disabled.')
            );
            return;
        }

        $filter = $this->searchCriteriaBuilder
            ->addFilter('status', self::PENDING)
            ->addFilter('created_at', $this->getCreatedAt(), 'lteq')
            ->create();

        $orders = $this->orderRepository->getList($filter)->getItems();

        /** @var Order $order */
        foreach ($orders as $order) {
            if ($order->getPayment()->getMethod() == self::PAYMENT_METHOD) {
                $this->machpay->getMachPayStatus($order);
                $order->cancel();
                $order->setStatus($this->helper->getCanceledStatus());
                $order->setState(Order::STATE_CANCELED);
                $this->orderRepository->save($order);
            }
        }
    }

    /**
     * Retrieve formatted locale date
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        $timeInterval = $this->helper->getTimeInterval();
        $prevDate = date_create(date('Y-m-d H:i:s', strtotime("-{$timeInterval} min")));
        return $prevDate->format('Y-m-d H:i:s');
    }
}
