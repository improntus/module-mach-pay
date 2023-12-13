<?php

namespace Improntus\MachPay\Controller\Order;

use Improntus\MachPay\Model\MachPay;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;
use Magento\Framework\UrlInterface;


class Cancel implements ActionInterface
{

    private RequestInterface $request;
    private RedirectFactory $redirectFactory;
    private MachPay $machPay;
    private Session $session;
    private LoggerInterface $logger;
    private UrlInterface $url;

    public function __construct(RequestInterface $request,
                                RedirectFactory  $redirectFactory,
                                MachPay          $machPay,
                                Session          $session,
                                LoggerInterface  $logger,
                                UrlInterface     $url

    )
    {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->machPay = $machPay;
        $this->session = $session;
        $this->logger = $logger;
        $this->url = $url;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $order = $this->session->getLastRealOrder();
        $transactionId = $this->request->getParam('token');
        $isCanceled = $this->machPay->cancel($order, __("Canceled by the customer in checkout"), $transactionId);
        $isCanceled ? $this->session->restoreQuote() : $this->logger->error("Error canceling order: " . $order->getIncrementId());
        if ($isCanceled) {
            $this->logger->info("Order canceled by customer: " . $order->getIncrementId());
            $this->session->restoreQuote();
            $this->machPay->cancelOrderMachPay($order);

            return $this->redirectFactory->create()->setUrl(
                $this->url->getUrl(
                    'checkout',
                    ['_secure' => true]
            ));
        } else {
            $this->logger->error("Error canceling order: " . $order->getIncrementId());
            return $this->redirectFactory->create()->setUrl(
                $this->url->getUrl(
                    'checkout/cart',
                    ['_secure' => true]
                ));
        }
    }
}
