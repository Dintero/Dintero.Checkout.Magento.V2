<?php

namespace Dintero\Checkout\Api;

/**
 * Interface SessionManagementInterface
 *
 * @package Dintero\Checkout\Api
 */
interface SessionManagementInterface
{
    /**
     * @return \Dintero\Checkout\Api\Data\SessionInterface
     */
    public function getSession();

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @return \Dintero\Checkout\Api\Data\SessionInterface
     */
    public function updateSession(\Magento\Quote\Api\Data\CartInterface $cart);
}
