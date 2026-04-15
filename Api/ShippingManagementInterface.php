<?php

namespace Dintero\Checkout\Api;

use Dintero\Checkout\Api\Data\ShippingMethodInterface;

interface ShippingManagementInterface
{
    /**
     * Retrieve selected shipping option by quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return ShippingMethodInterface|null
     */
    public function getSelectedShippingOptionByQuote(\Magento\Quote\Model\Quote $quote);
}
