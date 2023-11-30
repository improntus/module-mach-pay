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
use Magento\Framework\UrlInterface;

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
     * @var Machpay
     */
    private $machpay;

    /**
     * @var UrlInterface
     */
    private UrlInterface $url;

    /**
     * @param Session $session
     * @param Machpay $machpay
     * @param RedirectFactory $redirectFactory
     * @param Data $helper
     * @param UrlInterface $url
     */
    public function __construct(
        Session $session,
        Machpay $machpay,
        RedirectFactory $redirectFactory,
        Data $helper,
        UrlInterface $url
    ) {
        $this->helper = $helper;
        $this->redirectFactory = $redirectFactory;
        $this->machpay = $machpay;
        $this->session = $session;
        $this->url = $url;
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
        $resultRedirect = $this->redirectFactory->create();
        $url = "{$this->helper->getCallBackUrl()}?error=noresponse";
        if ($response = $this->machpay->createTransaction($order)) {
            if (isset($response['error'])) {
                $message = "Order {$order->getIncrementId()} errors: \n";
                $message .= $response['error']['message'];
                $this->helper->log($response['error']['message']);
                $this->session->setMachPayError($message);
                $url = "{$this->helper->getCallBackUrl()}?error=true";
            } elseif (isset($response['status'])) {
                $this->machpay->persistTransaction($order, $response, 'create');
                if ($this->helper->isMobile()) {
                    $url = $response['url'];
                } else {
                    if ($this->helper->getConfigData('custom_qr')) {
                        $token = $this->machpay->getMachPayToken($order->getId());
                        $response = $this->machpay->getMachQr($token);
                        if (isset($response['qr'])) {
                            $url = 'machpay/order/pay';
                            $resultRedirect->setUrl(
                                $this->url->getUrl(
                                    $url,
                                    ['qr' => $response['qr'], 'token' => $token]
                                )
                            );
                            return $resultRedirect;
                        }
                    } else {
                        $url = $response['url'];
                    }
                }
            } else {
                $url = "{$this->helper->getCallBackUrl()}?error=noresponse";
            }
        }
        $resultRedirect->setUrl($url);
        return $resultRedirect;
    }
}
