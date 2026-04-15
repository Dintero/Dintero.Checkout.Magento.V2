<?php

namespace Dintero\Checkout\Api\Data;

interface ItemInterface
{
    /*
     * Id
     */
    public const ID = 'id';

    /*
     * VAT
     */
    public const VAT = 'vat';

    /*
     * Amount
     */
    public const AMOUNT = 'amount';

    /*
     * Line ID
     */
    public const LINE_ID = 'line_id';

    /*
     * Quantity
     */
    public const QUANTITY = 'quantity';

    /*
     * VAT Amount
     */
    public const VAT_AMOUNT = 'vat_amount';

    /*
     * Description
     */
    public const DESCRIPTION = 'description';

    /*
     * Discount lines
     */
    public const DISCOUNT_LINES = 'discount_lines';

    /**
     * Define Id
     *
     * @param string $id
     * @return ItemInterface
     */
    public function setId($id);

    /**
     * Define VAT pernce
     *
     * @param float $vat
     * @return ItemInterface
     */
    public function setVat($vat);

    /**
     * Define amount
     *
     * @param float $amount
     * @return ItemInterface
     */
    public function setAmount($amount);

    /**
     * Define line id
     *
     * @param string $lineId
     * @return ItemInterface
     */
    public function setLineId($lineId);

    /**
     * Define quantity
     *
     * @param integer $qty
     * @return ItemInterface
     */
    public function setQuantity($qty);

    /**
     * Define VAT amount
     *
     * @param float $amount
     * @return ItemInterface
     */
    public function setVatAmount($amount);

    /**
     * Define description
     *
     * @param string $description
     * @return ItemInterface
     */
    public function setDescription($description);

    /**
     * Retrieve Id
     *
     * @return string
     */
    public function getId();

    /**
     * Retrieve VAT percent
     *
     * @return float
     */
    public function getVat();

    /**
     * Retrieve amount
     *
     * @return float
     */
    public function getAmount();

    /**
     * Retrieve line id
     *
     * @return string
     */
    public function getLineId();

    /**
     * Retrieve quantity
     *
     * @return integer
     */
    public function getQuantity();

    /**
     * Define description
     *
     * @return float
     */
    public function getVatAmount();

    /**
     * Retrieve description
     *
     * @return string
     */
    public function getDescription();

    /**
     * Define discount lines
     *
     * @param \Dintero\Checkout\Api\Data\DiscountInterface[] $discountLines
     * @return ItemInterface
     */
    public function setDiscountLines($discountLines);

    /**
     * Retrieve discount lines
     *
     * @return \Dintero\Checkout\Api\Data\DiscountInterface[]|array
     */
    public function getDiscountLines();
}
