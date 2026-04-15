<?php

namespace Dintero\Checkout\Api\Discount;

use Dintero\Checkout\Api\Data\Discount\RuleInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

interface RuleManagementInterface
{
    /**
     * Retrieve applicable rules for the quote
     *
     * @param Quote|Order $salesObject
     * @return RuleInterface[]|array
     */
    public function getQuoteApplicableRules($salesObject);

    /**
     * Create discount rule from quote
     *
     * @param Quote $quote
     * @return RuleInterface
     */
    public function createFromQuote(\Magento\Quote\Model\Quote $quote);

    /**
     * Create discount rule from order
     *
     * @param Order $order
     * @return RuleInterface
     */
    public function createFromOrder(\Magento\Sales\Model\Order $order);

    /**
     * Create discount rule from sales item
     *
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item $salesItem
     * @return RuleInterface
     */
    public function createFromItem($salesItem);
}
