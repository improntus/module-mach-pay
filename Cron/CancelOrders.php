<?php

namespace Improntus\MachPay\Cron;

use Improntus\MachPay\Api\TransactionRepositoryInterface;
use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\Machpay;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;

class CancelOrders
{
    private const PENDING = 'pending';
    private const PAYMENT_METHOD = 'machpay';
    private const TRANSACTION_CANCELED = 'canceled';
    private const EXPIRED = 'expired';

    private OrderRepositoryInterface $orderRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private Data $helper;
    private Machpay $machpay;
    private OrderCollection $orderCollection;
    private TransactionRepositoryInterface $transactionRepository;
    private TimezoneInterface $timezone;

    /**
     * CancelOrdersPending constructor
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Data $helper
     * @param Machpay $machpay
     * @param OrderCollection $orderCollection
     * @param TransactionRepositoryInterface $transactionRepository
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Data $helper,
        Machpay $machpay,
        OrderCollection $orderCollection,
        TransactionRepositoryInterface $transactionRepository,
        TimezoneInterface $timezone
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper = $helper;
        $this->machpay = $machpay;
        $this->orderCollection = $orderCollection;
        $this->transactionRepository = $transactionRepository;
        $this->timezone = $timezone;
    }

    /**
     * Cronjob Description
     *
     * @return void
     * @throws \Exception
     */
    public function cancelPending(): void
    {
        if (!$this->helper->isCronEnabled()) {
            $this->helper->log(
                __('Cancel orders Machpay is disabled.')
            );
            return;
        }

        $orderCollection = $this->getOrderCollection($this->getCreatedAt());

        /** @var Order $order */
        foreach ($orderCollection as $order) {
            $this->machpay->getMachPayStatus($order);
            if ($order->getState() !== Order::STATE_NEW ||
                $order->getStatus() === Order::STATE_PAYMENT_REVIEW) {
                continue;
            }
            $message = (__("Order canceled by cron after {$this->helper->getCancelHours()} hours pending."));
            $this->machpay->cancelOrder($order, $message);
            $this->updateTransactionGrid($order->getEntityId());
        }
    }

    /**
     * Cronjob Description
     *
     * @return void
     * @throws \Exception
     */
    public function cancelExpired(): void
    {
        if (!$this->helper->isCronEnabled()) {
            $this->helper->log(
                __('Expired orders Machpay is disabled.')
            );
            return;
        }

        $transactionCollection = $this->getTransactionCollection(self::PENDING);

        foreach ($transactionCollection as $transaction) {
            /** @var order $order */
            $order = $this->orderRepository->get($transaction->getOrderId());
            if ($order->getState() !== Order::STATE_NEW) {
                continue;
            }
            $timeZone = new \DateTimeZone($this->timezone->getConfigTimezone());
            $currentTime = date_create(date('Y-m-d H:i:s', strtotime("now")), $timeZone);
            $expiredAt = date_create(date('Y-m-d H:i:s', strtotime($transaction->getExpiredAt())), $timeZone);

            if ($expiredAt->format('Y-m-d H:i:s') < $currentTime->format('Y-m-d H:i:s')) {
                $message = (__('Order canceled due to expiration time.'));
                $this->machpay->cancelOrder($order, $message);
                $transaction->setStatus(self::EXPIRED);
                $this->transactionRepository->save($transaction);
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
        $timeInterval = $this->helper->getCancelHours();
        $prevDate = date_create(date('Y-m-d H:i:s', strtotime("-{$timeInterval} hour")));
        return $prevDate->format('Y-m-d H:i:s');
    }

    /**
     * Get Order collection filter by status and payment method
     *
     * @param string $createdAt
     * @return OrderCollection
     */
    private function getOrderCollection(string $createdAt)
    {
        $this->orderCollection->getSelect()
            ->joinLeft(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                ['method']
            )
            ->where('sop.method = ?', $this::PAYMENT_METHOD);
        $this->orderCollection->addFieldToFilter('main_table.status', self::PENDING)
            ->addFieldToFilter('main_table.created_at', ['lteq' => $createdAt])
            ->setOrder('main_table.created_at', 'ASC');
        return $this->orderCollection;
    }

    /**
     * Get Transaction collection
     *
     * @param string $status
     * @return \Improntus\MachPay\Api\Data\TransactionInterface[]
     * @throws LocalizedException
     */
    private function getTransactionCollection(string $status)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', $status)
            ->create();
        return $this->transactionRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Update transaction records
     *
     * @param string|int|float $orderId
     * @return void
     * @throws LocalizedException
     */
    private function updateTransactionGrid($orderId)
    {
        $transaction = $this->transactionRepository->getByOrderId($orderId);
        if ($transaction) {
            $transaction->setStatus(self::TRANSACTION_CANCELED)->save();
        }
    }
}
