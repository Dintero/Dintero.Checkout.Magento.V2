<?php

namespace Dintero\Checkout\Model\Api\Request\Generator;

use Dintero\Checkout\Model\Api\Request\GeneratorInterface;

class Combined implements GeneratorInterface
{
    /**
     * Use quote item id + sku as unique identifier for line id
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     */
    public function execute($item): string
    {
        return implode('-', [$item->getId(), $item->getSku()]);
    }
}
