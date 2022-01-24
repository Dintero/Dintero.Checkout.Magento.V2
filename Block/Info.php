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
        $info->setData('Payment Type', $this->getInfo()->getAdditionalInformation('payment_product'));
        return $info;
    }
}
