<?php

namespace Dintero\Checkout\Model\Api\Request\Generator;

use Dintero\Checkout\Model\Api\Request\GeneratorInterface;

class QuoteItemId implements GeneratorInterface
{
    /**
     * Use quote item id as line id field
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param string $type
     * @return string
     */
    public function execute($item): string
    {
        return $item->getId();
    }
}
