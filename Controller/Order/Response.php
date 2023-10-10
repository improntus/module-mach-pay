<?php

namespace Improntus\MachPay\Controller\Order;

use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\MachPay;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

class Response implements ActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
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
     * @var \Improntus\MachPay\Model\MachPay
     */
    private $machPay;

    /**
     * @param Session $session
     * @param MachPay $machPay
     * @param RedirectFactory $redirectFactory
     * @param Data $helper
     * @param RequestInterface $request
     */
    public function __construct(
        Session $session,
        MachPay $machPay,
        RedirectFactory $redirectFactory,
        Data $helper,
        RequestInterface $request
    ) {
        $this->request = $request;
        $this->helper = $helper;
        $this->redirectFactory = $redirectFactory;
        $this->machPay = $machPay;
        $this->session = $session;
    }

    /**
     * Process transaction of MachPay
     *
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->redirectFactory->create();
        $path = 'checkout/onepage/failure';
        $result = $this->request->getParams();
        if (isset($result['error'])) {
            if ($result['error'] == 'true') {
                $message = $this->session->getMachPayError();
                $this->session->setErrorMessage($message);
            } elseif ($result['error'] == 'noresponse') {
                $message = (
                    __('There was a problem retrieving data from MachPay. Wait for status confirmation from MachPay.')
                );
                $this->session->setErrorMessage($message);
            }
        } elseif (isset($result['status'])) {
            $transactionId = $result['business_payment_id'];
            $order = $this->session->getLastRealOrder();
            if (!isset($result['token']) || $result['token'] !== $this->helper->getApiToken($order)) {
                $message = (__('Invalid Token.'));
                $this->machPay->cancelOrder($order, $message);
                $this->session->setErrorMessage($message);
                $resultRedirect->setPath($path);
                return $resultRedirect;
            }
            $this->machPay->persistTransaction($order, $result);
            if ($result['status'] == 'PENDING') {
                $this->machPay->addSuccessToStatusHistory($order);
                $path = 'checkout/onepage/success';
            } else {
                $message = (__('Unexpected response from MachPay'));
                if ($result['status'] == 'canceled') {
                    $message = (__('The payment was cancelled by MachPay.'));
                } elseif ($result['status'] == 'expired') {
                    $message = (__('The payment date has expired.'));
                }
                $this->session->setErrorMessage($message);
                $this->machPay->cancelOrder($order, $message);
            }
        }
        $resultRedirect->setPath($path);
        return $resultRedirect;
    }
}
