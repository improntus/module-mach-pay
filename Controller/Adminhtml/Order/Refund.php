<?php

namespace Improntus\MachPay\Controller\Adminhtml\Order;

use Improntus\MachPay\Logger\Logger;
use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\Machpay;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class Refund extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Magento_Sales::actions_view';

    /**
     * @var Data
     */
    private Data $helper;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var Machpay
     */
    private Machpay $machpay;

    /**
     * @param Context $context
     * @param Data $helper
     * @param OrderRepositoryInterface $orderRepository
     * @param Machpay $machpay
     */
    public function __construct(
        Context $context,
        Data $helper,
        OrderRepositoryInterface $orderRepository,
        Machpay $machpay
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
        $this->machpay = $machpay;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($this->getRequest()->getParam('order_id'));
        try {
            $totalInvoice = 0;
            $invoices =  $order->getInvoiceCollection();
            /** @var Order\Invoice $invoice */
            foreach ($invoices->getItems() as $invoice) {
                $totalInvoice += $invoice->getGrandTotal();
            }
            if ($totalInvoice > 0) {
                $request = $this->machpay->createRefundMachPay($order, $totalInvoice);
                if ($request['success']) {
                    $order->setStatus('refund_initial');
                    $message = ($request['msg']);
                    $order->addCommentToStatusHistory($message, 'refund_initial');
                    $this->orderRepository->save($order);
                    $this->messageManager->addSuccessMessage(__('Order has been Refunded Successfully'));
                } else {
                    $order->setStatus('refund_failed');
                    $message = ($request['msg']);
                    $order->addCommentToStatusHistory($message, 'refund_failed');
                    $this->orderRepository->save($order);
                    $this->messageManager->addWarningMessage(__('Refund to Machpay failed'));
                }
            }
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            $this->messageManager->addErrorMessage(__('Refund to Machpay failed'));
        }
        return $resultRedirect->setPath('sales/order/view', [ 'order_id' => $order->getId() ]);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
