<?php

namespace Dintero\Checkout\Block;

use Magento\Framework\View\Element\Template;

/**
 * Class Express
 *
 * @package Dintero\Checkout\Block
 */
class Express
    extends \Magento\Framework\View\Element\Template
    implements \Magento\Catalog\Block\ShortcutInterface
{
    /**
     * @return string
     */
    public function getAlias()
    {
        return 'dintero.minicart.express';
    }

    /**
     * Retrieving express checkout url
     *
     * @return string
     */
    public function getExpressCheckoutUrl()
    {
        return $this->getUrl('dintero/checkout/express');
    }
}
