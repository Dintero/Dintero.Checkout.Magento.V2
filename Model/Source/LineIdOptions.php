<?php

namespace Dintero\Checkout\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Line id field options
 */
class LineIdOptions implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'sku',
                'label' => __('SKU'),
            ],
            [
                'value' => 'quote_item_id',
                'label' => __('Quote Item Id'),
            ],
            [
                'value' => 'quote_item_id_sku',
                'label' => __('Quote Item Id + SKU'),
            ]
        ];
    }
}
