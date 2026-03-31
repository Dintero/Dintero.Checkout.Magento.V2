<?php
namespace Dintero\Checkout\Api\Data;

interface DiscountInterface
{
    /*
     * Amount
     */
    public const AMOUNT = 'amount';

    /*
     * Line id
     */
    public const LINE_ID = 'line_id';

    /*
     * Percentage
     */
    public const PERCENTAGE = 'percentage';

    /*
     * Description
     */
    public const DESCRIPTION = 'description';

    /*
     * Discount ID
     */
    public const DISCOUNT_ID = 'discount_id';

    /*
     * Discount type
     */
    public const DISCOUNT_TYPE = 'discount_type';

    /**
     * Retrieve amount
     *
     * @return int
     */
    public function getAmount();

    /**
     * Populate amount
     *
     * @param int $amount
     * @return $this
     */
    public function setAmount(int $amount);

    /**
     * Retrieve line id
     *
     * @return int
     */
    public function getLineId();

    /**
     * Define line id
     *
     * @param int $lineId
     * @return $this
     */
    public function setLineId($lineId);

    /**
     * Retrieve percentage
     *
     * @return int|float|null
     */
    public function getPercentage();

    /**
     * Define percentage
     *
     * @param int|float $percentage
     * @return $this
     */
    public function setPercentage($percentage);

    /**
     * Retrieve description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Define description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * Retrieve discount id
     *
     * @return string
     */
    public function getDiscountId();

    /**
     * Define discount id
     *
     * @param string $discountId
     * @return $this
     */
    public function setDiscountId($discountId);

    /**
     * Retrieve discount type
     *
     * @return string
     */
    public function getDiscountType();

    /**
     * Define discount type
     *
     * @param string $discountType
     * @return $this
     */
    public function setDiscountType($discountType);
}
