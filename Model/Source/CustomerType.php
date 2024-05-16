<?php

namespace Dintero\Checkout\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Dintero Customer Type options
 */
class CustomerType implements OptionSourceInterface
{
    /*
     * Customer Type B2C
     */
    const TYPE_B2C = 'b2c';

    /*
     * Customer Type B2B
     */
    const TYPE_B2B = 'b2b';

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::TYPE_B2C,
                'label' => __('B2C'),
            ],
            [
                'value' => self::TYPE_B2B,
                'label' => __('B2B'),
            ],
        ];
    }
}
