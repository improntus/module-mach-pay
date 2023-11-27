<?php

namespace Improntus\MachPay\Model;

use Improntus\MachPay\Api\TransactionRepositoryInterface;
use Improntus\MachPay\Model\Api\Callback;
use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\Rest\Webservice;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface as PaymentTransactionRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Class Machpay - Model payment of machpay
 * @package Improntus\MachPay\Model\Payment
 */
class Machpay
{
    public const PENDING = "PENDING";
    public const COMPLETED = "COMPLETED";
    public const EXPIRED = "EXPIRED";
    public const REVERSED = "REVERSED";
    public const CONFIRMED = "CONFIRMED";
    public const FAILED = "FAILED";
    public const CANCELED = "CANCELED";
    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $paymentRepository;
    /**
     * @var InvoiceManagementInterface
     */
    private $invoiceManagement;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var PaymentTransactionRepository
     */
    private $paymentTransactionRepository;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var Webservice
     */
    private $ws;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CreditmemoManagementInterface
     */
    private $creditmemoManagement;
    private Order\CreditmemoFactory $creditmemoFactory;

    /**
     * @param Data $helper
     * @param InvoiceManagementInterface $invoiceManagement
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentTransactionRepository $paymentTransactionRepository
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderSender $orderSender
     * @param TransactionRepositoryInterface $transactionRepository
     * @param TransactionFactory $transactionFactory
     * @param Webservice $ws
     * @param ResourceConnection $resourceConnection
     * @param CreditmemoManagementInterface $creditmemoManagement
     * @param CreditmemoFactory $creditmemoFactory
     */
    public function __construct(
        Data $helper,
        InvoiceManagementInterface $invoiceManagement,
        OrderPaymentRepositoryInterface $paymentRepository,
        OrderRepositoryInterface $orderRepository,
        PaymentTransactionRepository $paymentTransactionRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderSender $orderSender,
        TransactionRepositoryInterface $transactionRepository,
        TransactionFactory $transactionFactory,
        Webservice $ws,
        ResourceConnection $resourceConnection,
        CreditmemoManagementInterface $creditmemoManagement,
        Order\CreditmemoFactory $creditmemoFactory
    ) {
        $this->orderSender = $orderSender;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->invoiceManagement = $invoiceManagement;
        $this->helper = $helper;
        $this->transactionRepository = $transactionRepository;
        $this->transactionFactory = $transactionFactory;
        $this->ws = $ws;
        $this->resourceConnection = $resourceConnection;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->creditmemoFactory = $creditmemoFactory;
    }

    /**
     * Create Transaction for MachPay
     *
     * @param Order $order
     * @return false|mixed|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function createTransaction(Order $order)
    {
        $data = $this->getRequestData($order);
        try {
            $response = $this->ws->doRequest($this->helper::MERCHANT_PAYMENTS, $data);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            throw new \Exception($e->getMessage());
        }
        return $response ?? false;
    }

    /**
     * Create data for endpoint to MachPay
     *
     * @param Order $order
     * @return array
     */
    private function getRequestData(Order $order)
    {
        $incrementId = $order->getIncrementId();
        $storeName = $order->getStore()->getName();
        return [
            'payment' => [
                'amount' => round($order->getGrandTotal(), 2),
                'message' => __('Order %1 Purchase from %2', $incrementId, $storeName),
                'title' => __('Purchase from %1', $storeName),
                'upstream_id' => (string)$incrementId,
            ]
        ];
    }

    /**
     * Get Customer data of order
     *
     * @param Order $order
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCustomerData($order)
    {
        $address = $order->getBillingAddress();
        return [
            'document_number' => $order->getCustomerTaxvat() ?? '',
            'first_name' => $address->getFirstname(),
            'last_name' => $address->getLastname(),
            'email' => $order->getCustomerEmail(),
            'phone_number' => $address->getTelephone() ?? '',
            'shipping_postal_code' => $address->getPostcode() ?? '',
            'shipping_address' => "{$address->getStreetLine(1)}
            {$address->getStreetLine(2)} {$address->getStreetLine(3)} {$address->getStreetLine(4)}",
        ];
    }

    /**
     * Create Invoice for order
     *
     * @param Order $order
     * @param string $transactionId
     * @return bool
     */
    public function invoice(Order $order, string $transactionId)
    {
        if (!$order->canInvoice() || $order->hasInvoices()) {
            return false;
        }
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            $invoice = $this->invoiceManagement->prepareInvoice($order);
            $invoice->register();
            $this->orderRepository->save($order);
            $invoice->setTransactionId($transactionId);
            $payment = $order->getPayment();
            $this->paymentRepository->save($payment);
            $transaction = $this->generateTransaction($payment, $invoice, $transactionId);
            $transaction->setAdditionalInformation('amount', round($order->getGrandTotal(), 2));
            $transaction->setAdditionalInformation('currency', $order->getStoreCurrencyCode());
            $this->paymentTransactionRepository->save($transaction);

            if (!$order->getEmailSent()) {
                $this->orderSender->send($order);
                $order->setIsCustomerNotified(true);
            }
            $invoice->pay();
            $invoice->getOrder()->setIsInProcess(true);
            $payment->addTransactionCommentsToOrder($transaction, __('Powerpay'));
            $this->invoiceRepository->save($invoice);
            $message = (__('Payment confirmed by PowerPay'));
            $order->addCommentToStatusHistory($message, Order::STATE_PROCESSING);
            $this->orderRepository->save($order);
            $ppTransaction = $this->transactionRepository->get($transactionId);
            $ppTransaction->setStatus('processed');
            $this->transactionRepository->save($ppTransaction);
            $connection->commit();
            return true;
        } catch (\Exception $e) {
            $connection->rollBack();
            $message = "Invoice creating for order {$order->getIncrementId()} failed: \n";
            $message .= $e->getMessage() . "\n";
            $this->helper->log($message);
            return false;
        }
    }

    /**
     * Refund transaction
     *
     * @param Order $order
     * @param string $transactionId
     * @return bool
     * @throws LocalizedException
     */
    public function refund(Order $order, string $transactionId)
    {
        if (!$order->canCreditmemo() || $order->hasCreditmemos()) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            if ($this->validateTransactionCreation($transactionId)) {
                return false;
            }
            $invoices = $order->getInvoiceCollection();
            if (count($invoices) == 0) {
                throw new \Exception(
                    __(
                        'No Invoices found for Refund. Order: %2',
                        $order->getIncrementId()
                    )
                );
            }
            $creditMemo = $this->creditmemoFactory->createByOrder($order);
            $creditMemo->setTransactionId($transactionId);

            $creditMemo->setCustomerNote(
                __(
                    'Your Order %1 has been Refunded',
                    $order->getIncrementId()
                )
            );
            $creditMemo->setCustomerNoteNotify(false);
            $creditMemo->addComment(__('Order has been Refunded by MachPay'));
            $order->addCommentToStatusHistory(__('Order has been Refunded Successfully'));

            $this->creditmemoManagement->refund($creditMemo);
            $ppTransaction = $this->transactionRepository->get($transactionId);
            $ppTransaction->setStatus('refunded');
            $this->transactionRepository->save($ppTransaction);
            $connection->commit();
            return true;

        } catch (\Exception $e) {
            $connection->rollBack();
            $message = "Credit memo creating for order {$order->getIncrementId()} failed: \n";
            $message .= $e->getMessage() . "\n";
            $this->helper->log($message);
            return false;
        }
    }

    /**
     * Generate Transaction
     *
     * @param $payment
     * @param $invoice
     * @param string $paypalTransaction
     * @return mixed
     */
    private function generateTransaction($payment, $invoice, string $transactionId)
    {
        $payment->setTransactionId($transactionId);
        return $payment->addTransaction(TransactionInterface::TYPE_CAPTURE, $invoice, true);
    }

    /**
     * Persist a transaction
     *
     * @param Order $order
     * @param array $result
     * @param string $flow
     * @return void
     */
    public function persistTransaction(Order $order, array $result, string $flow = 'response')
    {
        try {
            if ($flow !== 'response') {
                $transactionId = $result['token'];
            } else {
                $transactionId = $result['business_payment_id'];
            }
            $status = strtolower($result['status'] ?? '');
            if (!$this->transactionRepository->getByOrderId($order->getId())) {
                $transaction = $this->transactionFactory->create();
                $transaction->setOrderId($order->getId());
                $transaction->setMachPayTransactionId($transactionId ?? '');
                $transaction->setStatus($status);
                if (isset($result['created_at'])) {
                    $transaction->setCreatedAt($result['created_at']);
                }
            } else {
                $transaction = $this->transactionRepository->get($transactionId);
                $transaction->setStatus($status);
            }
            $transaction->setExpiredAt($result['expired_at'] ?? '');
            $this->transactionRepository->save($transaction);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }

    /**
     * Cancel order
     *
     * @param Order $order
     * @param Phrase $message
     * @return bool
     */
    public function cancelOrder(Order $order, Phrase $message)
    {
        try {
            if ($order->canCancel()) {
                $order->cancel();
                $order->setState(Order::STATE_CANCELED);
                $order->addCommentToStatusHistory($message, Order::STATE_CANCELED);
                $this->orderRepository->save($order);
                return true;
            }
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            return false;
        }
        return false;
    }

    /**
     * Get Order by transaction Id
     *
     * @param string $id
     * @return false|OrderInterface
     * @throws LocalizedException
     */
    public function getOrderByTransactionId(string $id)
    {
        $transaction = $this->transactionRepository->get($id);
        if ($transaction->getStatus()) {
            return $this->orderRepository->get($transaction->getOrderId());
        }
        return false;
    }

    /**
     * Check if transaction exists
     *
     * @param string $id
     * @return false|\Improntus\MachPay\Api\Data\TransactionInterface
     */
    public function checkIfExists(string $id)
    {
        try {
            return $this->transactionRepository->get($id);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            return false;
        }
    }

    /**
     * Add status if order is confirmed in MachPay
     *
     * @param Order $order
     * @return void
     */
    public function addSuccessToStatusHistory(Order $order)
    {
        if ($order->getState() === Order::STATE_NEW) {
            $message = (__('Payment confirmed by MachPay, awaiting capture.'));
            $order->addCommentToStatusHistory($message, Order::STATE_PAYMENT_REVIEW);
            $this->orderRepository->save($order);
        }
    }

    /**
     * Get Mach Pay Token
     *
     * @param $orderId
     * @return string|null
     * @throws LocalizedException
     */
    public function getMachPayToken($orderId)
    {
        $transaction = $this->transactionRepository->getByOrderId($orderId);
        return $transaction->getTransactionId();
    }


    /**
     * Get Mach Pay Status order
     *
     * @param Order $order
     * @return bool|\Magento\Framework\Webapi\Exception|mixed|string
     * @throws \Exception
     */
    public function getMachPayStatus(Order $order)
    {
        try {
            $response = false;
            $token = $this->getMachPayToken($order->getId());
            $request = $this->ws->doRequest($this->helper::MERCHANT_PAYMENTS, $token, "GET");
            if (isset($request['status'])) {
                switch ($request['status']) {
                    case self::COMPLETED || self::CONFIRMED:
                        if ($this->invoice($order, $token)) {
                            $response = true;
                        } else {
                            $response = new \Magento\Framework\Webapi\Exception(__('Order could not be invoiced.'));
                        }
                        break;
                    case self::EXPIRED || self::FAILED || self::REVERSED:
                        $this->cancelOrder($order, __('Canceled by Cron'));
                        $response = true;
                        break;
                    default:
                        $response = true;
                        $this->helper->log(__('Pending Status in Mach Pay'));
                }
            }
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            throw new \Exception($e->getMessage());
        }
        return $response;
    }
      
    /** Validate if credit memo will be created
     *
     * @param string $transactionId
     * @return bool
     * @throws LocalizedException
     */
    public function validateTransactionCreation(string $transactionId)
    {
        $transaction = $this->transactionRepository->get($transactionId);
        $dateToday = date_create(date('Y-m-d H:i:s', strtotime("-14 day")));
        if ($dateToday->format('Y-m-d H:i:s') > $transaction->getCreatedAt()) {
            return false;
        }
        return true;
    }
}
