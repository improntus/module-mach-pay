<?php

namespace Improntus\MachPay\Controller\Order;

use Improntus\MachPay\Model\MachPay;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Checkout\Model\Session;

class getToken implements ActionInterface
{

    /**
     * @var \Improntus\MachPay\Model\MachPay
     */
    private $machPay;

    /**
     * @var ResultFactory
     */
    private $resultFactory;
    
    private $session;

    /**
     * @param MachPay $machPay
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        MachPay $machPay,
        ResultFactory $resultFactory,
        Session $session,
    ) {
        $this->machPay = $machPay;
        $this->resultFactory = $resultFactory;
        $this->session = $session;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface
     * @throws NotFoundException
     * @throws LocalizedException
     */
    public function execute()
    {
        $order = $this->session->getLastRealOrder();
        $token = $this->machPay->getMachPayToken($order->getId()) ?? '';
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['token' => $token]);
        return $resultJson;
    }
}
