<?php

namespace Dintero\Checkout\Model\Api\Request\Generator;

use Dintero\Checkout\Model\Api\Request\GeneratorInterface;

class Sku implements GeneratorInterface
{
    /**
     * Use SKU as line item id
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     */
    public function execute($item): string
    {
        return $item->getSku();
    }
}
