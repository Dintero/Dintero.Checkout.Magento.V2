<?php

namespace Dintero\Checkout\Block;

class Info extends \Magento\Payment\Block\Info
{
    /**
     * @param $transport
     * @return \Magento\Framework\DataObject|null
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $info = parent::_prepareSpecificInformation($transport);

        if ($paymentType = $this->getInfo()->getAdditionalInformation('payment_product')) {
            $info->setData('Payment Type', $paymentType);
        }

        if ($paymentLink = $this->getInfo()->getAdditionalInformation('payment_link')) {
            $info->setData('Payment Link', $paymentLink);
        }
        return $info;
    }
}
