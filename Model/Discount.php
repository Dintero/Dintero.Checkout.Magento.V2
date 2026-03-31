<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Api\Data\DiscountInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class Discount extends AbstractExtensibleObject implements DiscountInterface
{
    /**
     * Retrieve amount
     *
     * @return int
     */
    public function getAmount()
    {
        return $this->_get(self::AMOUNT);
    }

    /**
     * Define discount amount
     *
     * @param int $amount
     * @return Discount
     */
    public function setAmount(int $amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * Retrieve line id
     *
     * @return int
     */
    public function getLineId()
    {
        return $this->_get(self::LINE_ID);
    }

    /**
     * Define line id
     *
     * @param int $lineId
     * @return Discount
     */
    public function setLineId($lineId)
    {
        return $this->setData(self::LINE_ID, $lineId);
    }

    /**
     * Retrieve percentage
     *
     * @return float|int|null
     */
    public function getPercentage()
    {
        return $this->_get(self::PERCENTAGE);
    }

    /**
     * Define percentage
     *
     * @param int|float|null $percentage
     * @return Discount
     */
    public function setPercentage($percentage)
    {
        return $this->setData(self::PERCENTAGE, $percentage);
    }

    /**
     * Define description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->_get(self::DESCRIPTION);
    }

    /**
     * Define description
     *
     * @param string|null $description
     * @return Discount
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Retrieve discount id
     *
     * @return string
     */
    public function getDiscountId()
    {
        return $this->_get(self::DISCOUNT_ID);
    }

    /**
     * Define discount id
     *
     * @param string $discountId
     * @return Discount
     */
    public function setDiscountId($discountId)
    {
        return $this->setData(self::DISCOUNT_ID, $discountId);
    }

    /**
     * Retrieve discount type
     *
     * @return string
     */
    public function getDiscountType()
    {
        return $this->_get(self::DISCOUNT_TYPE);
    }

    /**
     * Define discount type
     *
     * @param string $discountType
     * @return Discount
     */
    public function setDiscountType($discountType)
    {
        return $this->setData(self::DISCOUNT_TYPE, $discountType);
    }
}
