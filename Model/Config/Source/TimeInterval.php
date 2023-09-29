<?php

namespace Improntus\MachPay\Model\Config\Source;

/**
 * Class TimeInterval - Brief description of class objective
 * @package Improntus\MachPay\Model\Config\Source
 */
class TimeInterval implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
     * @var array
     */
    private $options;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->options = [['value' => '', 'label' => __('-- Please Select--')]];
        foreach (range(0, 100, 5) as $value) {
            $this->options[] = ['value' => $value, 'label' => $value];
        }
        return $this->options;
    }
}
