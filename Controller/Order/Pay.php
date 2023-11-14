<?php

namespace Improntus\MachPay\Controller\Order;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Element\Template;

/**
 * Class Pay - Redirect to custom page with QR
 * @package Improntus\MachPay\Controller\Order
 */
class Pay implements \Magento\Framework\App\ActionInterface
{

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var ResultFactory
     */
    private ResultFactory $resultFactory;

    /**
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        RequestInterface $request,
        ResultFactory $resultFactory,
    ) {
        $this->request = $request;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $qrCode = $this->request->getParam('qr');
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        /** @var Template $block */
        $block = $page->getLayout()->getBlock('machpay_qr');
        $block->setData('qr', base64_decode($qrCode));
        $block->setTemplate('Improntus_MachPay::pay.phtml');

        return $page;
    }
}
