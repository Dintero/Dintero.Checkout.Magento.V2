<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Api\Data\ShippingMethodInterface;

class Order extends \Magento\Framework\DataObject implements \Dintero\Checkout\Api\Data\OrderInterface
{
    /**
     * Define amount
     *
     * @param float $amount
     * @return \Dintero\Checkout\Api\Data\OrderInterface|Order
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * Retrieve amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * Define VAT amount
     *
     * @param float $amount
     * @return \Dintero\Checkout\Api\Data\OrderInterface|Order
     */
    public function setVatAmount($amount)
    {
        return $this->setData(self::VAT_AMOUNT, $amount);
    }

    /**
     * Retrieve VAT amount
     *
     * @return float
     */
    public function getVatAmount()
    {
        return $this->getData(self::VAT_AMOUNT);
    }

    /**
     * Define currency
     *
     * @param string $currency
     * @return \Dintero\Checkout\Api\Data\OrderInterface|Order
     */
    public function setCurrency($currency)
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    /**
     * Retrieve currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->getData(self::CURRENCY);
    }

    /**
     * Define order items
     *
     * @param array $items
     * @return \Dintero\Checkout\Api\Data\OrderInterface|Order
     */
    public function setItems($items)
    {
        return $this->setData(self::ITEMS, $items);
    }

    /**
     * Retrieve order items
     *
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface[]
     */
    public function getItems()
    {
        return $this->getData(self::ITEMS);
    }

    /**
     * Define discount codes
     *
     * @param string[] $discountCodes
     * @return \Dintero\Checkout\Api\Data\OrderInterface|Order
     */
    public function setDiscountCodes($discountCodes)
    {
        return $this->setData(self::DISCOUNT_CODES, $discountCodes);
    }

    /**
     * Retrieve discount code
     *
     * @return string[]
     */
    public function getDiscountCodes()
    {
        return $this->getData(self::DISCOUNT_CODES);
    }

    /**
     * Define discount line items
     *
     * @param \Dintero\Checkout\Api\Data\DiscountInterface[] $discountLines
     * @return \Dintero\Checkout\Api\Data\OrderInterface|Order
     */
    public function setDiscountLines($discountLines)
    {
        return $this->setData(self::DISCOUNT_LINES, $discountLines);
    }

    /**
     * Retrieve Discount line items
     *
     * @return array|\Dintero\Checkout\Api\Data\DiscountInterface[]
     */
    public function getDiscountLines()
    {
        return $this->getData(self::DISCOUNT_LINES) ?? [];
    }

    /**
     * Define shipping option
     *
     * @param ShippingMethodInterface $shippingMethod
     * @return \Dintero\Checkout\Api\Data\OrderInterface|Order
     */
    public function setShippingOption(ShippingMethodInterface $shippingMethod)
    {
        return $this->setData(self::SHIPPING_OPTION, $shippingMethod);
    }

    /**
     * Retrieve shipping option
     *
     * @return ShippingMethodInterface|null
     */
    public function getShippingOption()
    {
        return $this->getData(self::SHIPPING_OPTION) ?? null;
    }
}
