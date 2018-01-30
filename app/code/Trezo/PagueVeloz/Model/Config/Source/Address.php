<?php

namespace Trezo\PagueVeloz\Model\Config\Source;

class Address implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Select...')],
            ['value' => 1, 'label' => __('Street 1')],
            ['value' => 2, 'label' => __('Street 2')],
            ['value' => 3, 'label' => __('Street 3')],
            ['value' => 4, 'label' => __('Street 4')]
        ];
    }
}
