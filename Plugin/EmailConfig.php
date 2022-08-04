<?php

namespace Dintero\Checkout\Plugin;

use Magento\Email\Model\Template\Config as Subject;

class EmailConfig
{
    /**
     * Overriding template id logic
     *
     * @param Subject $subject
     * @param $templateId
     * @return mixed|string[]
     */
    public function beforeGetTemplateLabel(Subject $subject, $templateId)
    {
        if (strpos($templateId, 'dintero_payment_email') !== false) {
            return ['payment_dintero_payment_email'];
        }

        return $templateId;
    }
}
