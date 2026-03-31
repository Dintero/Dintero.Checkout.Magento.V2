<?php

namespace Dintero\Checkout\Model\Order;

class Item extends \Magento\Framework\DataObject implements \Dintero\Checkout\Api\Data\Order\ItemInterface
{
    /**
     * Retrieve Id
     *
     * @return string
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Define id
     *
     * @param string $id
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface|Item
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Define line id
     *
     * @param string $lineId
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
     */
    public function setLineId($lineId)
    {
        return $this->setData(self::LINE_ID, $lineId);
    }

    /**
     * Retrieve line id param
     *
     * @return string
     */
    public function getLineId()
    {
        return $this->getData(self::LINE_ID);
    }

    /**
     * Dfine quantity
     *
     * @param float $qty
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface|Item
     */
    public function setQuantity($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * Retrieve quantity value
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->getData(self::QTY);
    }

    /**
     * Define VAT percent
     *
     * @param float $vat
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface|Item
     */
    public function setVat($vat)
    {
        return $this->setData(self::VAT, $vat);
    }

    /**
     * Retrieve VAT percent
     *
     * @return float
     */
    public function getVat()
    {
        return $this->getData(self::VAT);
    }

    /**
     * Define VAT amount
     *
     * @param float $amount
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
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
     * Define amount
     *
     * @param float $amount
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface|Item
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
     * Define description
     *
     * @param string $description
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Retrieve description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Populate discount lines
     *
     * @param \Dintero\Checkout\Api\Data\DiscountInterface[] $discountLines
     * @return \Dintero\Checkout\Api\Data\ItemInterface|Item
     */
    public function setDiscountLines(array $discountLines)
    {
        return $this->setData(self::DISCOUNT_LINES, $discountLines);
    }

    /**
     * Retrieve discount lines
     *
     * @return array|\Dintero\Checkout\Api\Data\DiscountInterface[]
     */
    public function getDiscountLines()
    {
        return $this->getData(self::DISCOUNT_LINES) ?? [];
    }
}
