<?php

namespace Dintero\Checkout\Model\Api\Request\Builder;

use Dintero\Checkout\Api\Data\ItemInterfaceFactory as OrderItemInterfaceFactory;
use Dintero\Checkout\Api\Data\ItemInterface as OrderItemInterface;
use Dintero\Checkout\Model\Formatter\Amount as AmountFormatter;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;

class GiftCardItemBuilder
{
    /** @var OrderItemInterfaceFactory $orderItemFactory */
    private $orderItemFactory;

    /** @var AmountFormatter $amountFormatter */
    private $amountFormatter;

    public function __construct(
        OrderItemInterfaceFactory $orderItemFactory,
        AmountFormatter $amountFormatter
    ) {
        $this->orderItemFactory = $orderItemFactory;
        $this->amountFormatter = $amountFormatter;
    }

    /**
     * Build gift card item
     *
     * @param array $buildSubject
     * @return OrderItemInterface
     */
    public function build($buildSubject)
    {
        if (empty($buildSubject['sales_object'])) {
            throw new \InvalidArgumentException(__('Sales object is missing from subject.'));
        }

        $salesObject = $buildSubject['sales_object'];

        if ($salesObject instanceof Quote === false && $salesObject instanceof Order === false) {
            throw new \InvalidArgumentException(__('Sales object is missing from or is invalid.'));
        }

        $amount = $this->amountFormatter->filter(
            $salesObject->getBaseMageworxGiftcardsAmount()
        );

        if (empty($salesObject->getMageworxGiftcardsDescription())) {
            return null;
        }

        /** @var OrderItemInterface $lineItem */
        $lineItem = $this->orderItemFactory->create();
        $lineItem->setAmount($this->amountFormatter->format($amount))
            ->setId('gift-card')
            ->setLineId('gift-card')
            ->setDescription($salesObject->getMageworxGiftcardsDescription())
            ->setQuantity(1);
        return $lineItem;
    }
}
