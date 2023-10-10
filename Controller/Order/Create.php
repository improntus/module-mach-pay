<?php

namespace Improntus\MachPay\Controller\Order;

use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\Machpay;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Create implements ActionInterface
{
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var \Improntus\MachPay\Model\Machpay
     */
    private $machpay;

    /**
     * @param Session $session
     * @param \Improntus\MachPay\Model\Machpay $machpay
     * @param RedirectFactory $redirectFactory
     * @param Data $helper
     */
    public function __construct(
        Session     $session,
        Machpay $machpay,
        RedirectFactory $redirectFactory,
        Data $helper,
    ) {
        $this->helper = $helper;
        $this->redirectFactory = $redirectFactory;
        $this->machpay = $machpay;
        $this->session = $session;
    }

    /**
     * Create order transaction for MachPay
     *
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $order = $this->session->getLastRealOrder();
        if ($response = $this->machpay->createTransaction($order)) {
            if (isset($response['error'])) {
                $message = "Order {$order->getIncrementId()} errors: \n";
                $message .= $response['error']['message'];
                $this->helper->log($response['error']['message']);
                $this->session->setMachPayError($message);
                $url = "{$this->helper->getCallBackUrl()}?error=true";
            } elseif (isset($response['status'])) {
                if ($response['status'] >= 301 && $response['status'] <= 500) {
                    $message = $response['message'];
                    $this->helper->log($response['message']);
                    $this->session->setMachPayError($message);
                    $url = "{$this->helper->getCallBackUrl()}?error=true";
                } else {
                    $this->machpay->persistTransaction($order, $response, 'create');
                    $url = $response['url'];
                }
            } else {
                $url = "{$this->helper->getCallBackUrl()}?error=noresponse";
            }
        } else {
            $url = "{$this->helper->getCallBackUrl()}?error=noresponse";
        }
        $resultRedirect = $this->redirectFactory->create();
        $resultRedirect->setUrl($url);
        return $resultRedirect;
    }
}
