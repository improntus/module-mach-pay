<?php

namespace Improntus\MachPay\Controller\Order;

use Improntus\MachPay\Model\Config\Data;
use Improntus\MachPay\Model\Machpay;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;

class Validation implements ActionInterface
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
     * @var \Improntus\MachPay\Model\MachPay
     */
    private $machPay;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param MachPay $machPay
     * @param ResultFactory $resultFactory
     * @param Data $helper
     * @param RequestInterface $request
     */
    public function __construct(
        MachPay $machPay,
        ResultFactory $resultFactory,
        Data $helper,
        RequestInterface $request
    ) {
        $this->request = $request;
        $this->helper = $helper;
        $this->machPay = $machPay;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $token = isset($params['token']) ? $params['token'] : '';
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($this->machPay->getMachPayOrder($token));
        return $resultJson;
    }
}
