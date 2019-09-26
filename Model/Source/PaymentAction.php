<?php

namespace Dintero\Hp\Model\Source;

use Dintero\Hp\Model\Dintero;
use Magento\Framework\Option\ArrayInterface;

/**
 * Authorize.net Payment Action Dropdown source
 * @deprecated 100.3.1 Authorize.net is removing all support for this payment method
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
