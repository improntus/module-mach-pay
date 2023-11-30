<?php

namespace Improntus\MachPay\Controller\Order;

use Improntus\MachPay\Model\Config\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RequestInterface $request,
        ResultFactory $resultFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $qrCode = $this->request->getParam('qr');
        $token = $this->request->getParam('token');

        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set(__('MachPay QR'));

        /** @var Template $block */
        $block = $page->getLayout()->getBlock('machpay_qr');
        $qr = Data::MACHPAY_QR_FOLDER . "{$qrCode}.png";
        $block->setData('qr', $this->storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $qr);
        $block->setData('token', $token);
        $block->setTemplate('Improntus_MachPay::pay.phtml');

        return $page;
    }
}
