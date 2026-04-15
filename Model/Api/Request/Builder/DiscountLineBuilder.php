<?php

namespace Dintero\Checkout\Model\Api\Request\Builder;

use Dintero\Checkout\Api\Data\Discount\RuleInterface;
use Dintero\Checkout\Api\Data\DiscountInterfaceFactory;
use Dintero\Checkout\Model\Formatter\Amount as AmountFormatter;

class DiscountLineBuilder
{
    /** @var DiscountInterfaceFactory $discountFactory */
    private $discountFactory;

    /**
     * @var AmountFormatter $amountFormatter
     */
    private $amountFormatter;

    /**
     * Define class dependencies
     *
     * @param DiscountInterfaceFactory $discountFactory
     * @param AmountFormatter $amountFormatter
     */
    public function __construct(
        DiscountInterfaceFactory    $discountFactory,
        AmountFormatter             $amountFormatter
    ) {
        $this->discountFactory = $discountFactory;
        $this->amountFormatter = $amountFormatter;
    }

    /**
     * Build discount line item
     *
     * @param RuleInterface $rule
     * @return \Dintero\Checkout\Api\Data\DiscountInterface
     */
    public function build(RuleInterface $rule)
    {
        // phpcs:disable
        if (abs($rule->getAmount()) < 0.01) {
            return null;
        }
        // phpcs:enable

        /** @var \Dintero\Checkout\Api\Data\DiscountInterface $discount */
        $discount = $this->discountFactory->create();
        $discount
            ->setDiscountId(sprintf('dl-%s', $rule->getId()))
            ->setLineId($rule->getId())
            ->setDescription($rule->getDescription())
            // phpcs:disable
            ->setAmount(intval(abs($this->amountFormatter->format(
                $this->amountFormatter->filter($rule->getAmount())
            ))))->setDiscountType('customer');
            // phpcs:enable
        return $discount;
    }
}
