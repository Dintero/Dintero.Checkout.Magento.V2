<?php

namespace Dintero\Checkout\Model\Shipping;

use Dintero\Checkout\Api\Data\ShippingMethodInterface;
use Dintero\Checkout\Helper\Config;

class DeliveryTypeResolver
{
    /**
     * @var Config $configHelper
     */
    private $configHelper;

    /**
     * Define class dependencies
     *
     * @param Config $configHelper
     */
    public function __construct(Config $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * Resolve Delivery type by shipping method
     *
     * @param string $shippingMethod
     * @param string $scopeCode
     * @return string
     */
    public function resolve($shippingMethod, $scopeCode = null)
    {
        if (in_array($shippingMethod, $this->configHelper->getPickupMethods($scopeCode))) {
            return ShippingMethodInterface::DELIVERY_METHOD_PICKUP;
        }

        if (in_array($shippingMethod, $this->configHelper->getUnspecifiedMethods($scopeCode))) {
            return ShippingMethodInterface::DELIVERY_METHOD_UNSPECIFIED;
        }

        return ShippingMethodInterface::DELIVERY_METHOD_DELIVERY;
    }
}
