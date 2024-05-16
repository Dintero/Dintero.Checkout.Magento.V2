<?php

namespace Dintero\Checkout\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Dintero Embedded Type options
 */
class EmbedType implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Dintero\Checkout\Model\Api\Client::TYPE_EMBEDDED,
                'label' => __('Default'),
            ],
            [
                'value' => \Dintero\Checkout\Model\Api\Client::TYPE_EXPRESS,
                'label' => __('Express'),
            ],
        ];
    }
}
