<?php

namespace Dintero\Checkout\Model\Formatter;

class Amount
{
    /**
     * Format amount
     *
     * @param float|int $amount
     * @return int
     */
    public function format($amount)
    {
        return $amount * 100;
    }

    /**
     * Filter amount
     *
     * @param float $amount
     * @return string
     */
    public function filter($amount)
    {
        return sprintf('%f', $amount);
    }
}
