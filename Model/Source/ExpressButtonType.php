<?php

namespace Dintero\Checkout\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Dintero Payment Action Dropdown source
 */
class ExpressButtonType implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'Dintero_Checkout::images/dintero-express-btn-dark.svg',
                'label' => __('Dark'),
            ],
            [
                'value' => 'Dintero_Checkout::images/dintero-express-btn-light.svg',
                'label' => __('Light')
            ],
            [
                'value' => 'Dintero_Checkout::images/dintero-express-btn-light.svg',
                'label' => __('Transparent')
            ],
            [
                'value' => 'Dintero_Checkout::images/dintero-express-btn-round-dark.svg',
                'label' => __('Dark Round'),
            ],
            [
                'value' => 'Dintero_Checkout::images/dintero-express-btn-round-light.svg',
                'label' => __('Light Round')
            ],
        ];
    }
}
