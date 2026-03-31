<?php

namespace Dintero\Checkout\Model\Api\Request\Builder;

use Dintero\Checkout\Api\Data\Discount\RuleInterface;
use Dintero\Checkout\Api\Data\ItemInterfaceFactory as OrderItemInterfaceFactory;
use Dintero\Checkout\Api\Data\ItemInterface as OrderItemInterface;
use Dintero\Checkout\Api\Discount\RuleManagementInterface;
use Dintero\Checkout\Model\Formatter\Amount as AmountFormatter;
use Dintero\Checkout\Model\Api\Request\Builder\DiscountLineBuilder;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Item as OrderItem;

class OrderItemBuilder
{
    /** @var OrderItemInterfaceFactory $orderItemFactory */
    private $orderItemFactory;

    /** @var AmountFormatter $amountFormatter */
    private $amountFormatter;

    /** @var DiscountLineBuilder $discountLineBuilder */
    private $discountLineBuilder;

    /** @var RuleManagementInterface $ruleManagement */
    private $ruleManagement;

    /**
     * Define class dependencies
     *
     * @param OrderItemInterfaceFactory $orderItemFactory
     * @param AmountFormatter $amountFormatter
     * @param DiscountLineBuilder $discountLineBuilder
     * @param RuleManagementInterface $ruleManagement
     */
    public function __construct(
        OrderItemInterfaceFactory   $orderItemFactory,
        AmountFormatter             $amountFormatter,
        DiscountLineBuilder         $discountLineBuilder,
        RuleManagementInterface     $ruleManagement
    ) {
        $this->orderItemFactory = $orderItemFactory;
        $this->amountFormatter = $amountFormatter;
        $this->discountLineBuilder = $discountLineBuilder;
        $this->ruleManagement = $ruleManagement;
    }

    /**
     * Retrieve qty
     *
     * @param QuoteItem|OrderItem $salesItem
     * @return float|int|mixed|null
     */
    protected function getQty($salesItem)
    {
        return ($salesItem instanceof QuoteItem ? $salesItem->getQty() : $salesItem->getQtyOrdered()) * 1;
    }

    /**
     * Build line item
     *
     * @param array $buildSubject
     * @return OrderItemInterface
     */
    public function build(array $buildSubject)
    {
        $lineId = $buildSubject['line_id'] ?? null;
        if (empty($lineId)) {
            throw new \InvalidArgumentException(__('Line ID is missing from subject.'));
        }

        $salesItem = $buildSubject['item'];

        if ($salesItem instanceof QuoteItem === false && $salesItem instanceof OrderItem === false) {
            throw new \InvalidArgumentException(__('Item is missing from or is invalid.'));
        }

        $itemAmount = $this->amountFormatter->filter(
            $salesItem->getBaseRowTotalInclTax() - $salesItem->getBaseDiscountAmount()
        );

        /** @var OrderItemInterface $lineItem */
        $lineItem = $this->orderItemFactory->create();
        $lineItem->setAmount($this->amountFormatter->format($itemAmount))
            ->setId($salesItem->getSku())
            ->setLineId($lineId)
            ->setDescription(sprintf('%s (%s)', $salesItem->getName(), $salesItem->getSku()))
            ->setQuantity($this->getQty($salesItem))
            ->setVatAmount($this->amountFormatter->format($salesItem->getBaseTaxAmount()))
            ->setVat($salesItem->getTaxPercent() * 1);

        $appliedRuleIds = array_filter(explode(',', $salesItem->getAppliedRuleIds() ?? ''));
        if (!empty($appliedRuleIds) && $salesItem->getDiscountAmount() > 0.01) {
            $discountRule = $this->ruleManagement->createFromItem($salesItem);
            if ($discountLine = $this->discountLineBuilder->build($discountRule)) {
                $lineItem->setDiscountLines([$discountLine]);
            }
        }

        return $lineItem;
    }
}
