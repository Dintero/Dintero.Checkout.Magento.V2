<?php

namespace Dintero\Checkout\Model\Shipping;

use Dintero\Checkout\Api\Data\Shipping\ResponseInterface;

/**
 * Class ShippingOptions
 *
 * @package Dintero\Checkout\Model
 */
class Response implements \Dintero\Checkout\Api\Data\Shipping\ResponseInterface
{
    /**
     * @var \Dintero\Checkout\Api\Data\ShippingMethodInterface[] $shippingOptions
     */
    private $shippingOptions;

    /**
     * @param \Dintero\Checkout\Api\Data\ShippingMethodInterface[] $shippingOptions
     * @return $this|ResponseInterface
     */
    public function setShippingOptions($shippingOptions)
    {
        $this->shippingOptions = $shippingOptions;
        return $this;
    }

    /**
     * Retrieving shipping options
     *
     * @return \Dintero\Checkout\Api\Data\ShippingMethodInterface[]
     */
    public function getShippingOptions()
    {
        return $this->shippingOptions;
    }
}
