<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Model\Api\Request\Builder\ShippingOptionBuilder;
use Magento\Quote\Api\ShippingMethodManagementInterface;

class ShippingManagement implements \Dintero\Checkout\Api\ShippingManagementInterface
{
    /**
     * @var ShippingMethodManagementInterface $shippingMethodManagement
     */
    protected $shippingMethodManagement;

    /**
     * @var ShippingMethodManagementInterface $shippingOptionBuilder
     */
    protected $shippingOptionBuilder;

    /**
     * Define class dependencies
     *
     * @param ShippingMethodManagementInterface $shippingMethodManagement
     * @param ShippingOptionBuilder $shippingOptionBuilder
     */
    public function __construct(
        ShippingMethodManagementInterface $shippingMethodManagement,
        ShippingOptionBuilder $shippingOptionBuilder
    ) {
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->shippingOptionBuilder = $shippingOptionBuilder;
    }

    /**
     * Retrieve shipping selected shipping option from quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Dintero\Checkout\Api\Data\ShippingMethodInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function getSelectedShippingOptionByQuote(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getIsVirtual() || !$quote->getShippingAddress()) {
            return null;
        }

        $shippingAddress = $quote->getShippingAddress();

        $shippingMethods = $this->shippingMethodManagement->getList($quote->getId());
        foreach ($shippingMethods as $shippingMethod) {
            $carrierMethodCode = sprintf('%s_%s', $shippingMethod->getCarrierCode(), $shippingMethod->getMethodCode());
            if ($carrierMethodCode !== $shippingAddress->getShippingMethod()) {
                continue;
            }

            return $this->shippingOptionBuilder->build($shippingMethod, $quote->getStoreId());
        }
        return null;
    }
}
