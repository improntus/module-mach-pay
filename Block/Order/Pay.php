<?php

namespace Improntus\MachPay\Block\Order;

use Magento\Framework\View\Element\Template;

class Pay extends Template
{
    /**
     * @return string
     */
    public function getSuccessPage()
    {
        return $this->_urlBuilder->getUrl('checkout/onepage/success');
    }

    /**
     * @return string
     */
    public function getFailurePage()
    {
        return $this->_urlBuilder->getUrl('checkout/onepage/failure');
    }
}
