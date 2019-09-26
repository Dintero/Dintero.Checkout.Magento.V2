<?php

namespace Dintero\Hp\Model\Source;

use Dintero\Hp\Model\Dintero;
use Magento\Framework\Option\ArrayInterface;

/**
 * Dintero Payment Action Dropdown source
 */
class PaymentAction implements ArrayInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Dintero::ACTION_AUTHORIZE,
                'label' => __('Authorize Only'),
            ],
            [
                'value' => Dintero::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture')
            ]
        ];
    }
}
