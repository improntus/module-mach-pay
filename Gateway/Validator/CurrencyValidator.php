<?php

namespace Improntus\MachPay\Gateway\Validator;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Improntus\MachPay\Model\Config\Data as MachPayHelper;

/**
 * Class CurrencyValidator - Validate currency of website
 * @package Improntus\MachPay\Gateway\Validator
 */
class CurrencyValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{

    /**
     * @var MachPayHelper
     */
    private $machPayHelper;

    /**
     * Constructor
     *
     * @param ResultInterfaceFactory $resultInterfaceFactory
     * @param MachPayHelper $machPayHelper
     */
    public function __construct(
        ResultInterfaceFactory $resultInterfaceFactory,
        MachPayHelper $machPayHelper
    ) {
        parent::__construct($resultInterfaceFactory);
        $this->machPayHelper = $machPayHelper;
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $isValid = true;
        $fails = [];
        if ($validationSubject['currency'] != $this->machPayHelper->getCurrency($validationSubject['storeId'])) {
            $isValid = false;
            $fails[] = __('Currency doesn\'t match.');
        }
        return $this->createResult($isValid, $fails);
    }
}
