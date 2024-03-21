<?php

namespace Dintero\Checkout\Model\Api\Request;

interface GeneratorInterface
{
    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     */
    public function execute($item): string;
}
