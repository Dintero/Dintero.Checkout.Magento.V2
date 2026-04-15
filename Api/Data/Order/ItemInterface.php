<?php

namespace Dintero\Checkout\Api\Data\Order;

interface ItemInterface
{
    /*
     * Item ID
     */
    public const ID = 'id';

    /*
     * Line Id
     */
    public const LINE_ID = 'line_id';

    /*
     * Quantity
     */
    public const QTY = 'quantity';

    /*
     * Amount
     */
    public const AMOUNT = 'amount';

    /*
     * VAT amount
     */
    public const VAT_AMOUNT = 'vat_amount';

    /*
     * VAT
     */
    public const VAT = 'vat';

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
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
     */
    public function setId($id);

    /**
     * Retrieve Id
     *
     * @return string
     */
    public function getId();

    /**
     * Define line id
     *
     * @param string $lineId
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
     */
    public function setLineId($lineId);

    /**
     * Retrieve line id
     *
     * @return string
     */
    public function getLineId();

    /**
     * Define quantity
     *
     * @param float $qty
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
     */
    public function setQuantity($qty);

    /**
     * Retrieve quantity
     *
     * @return float
     */
    public function getQuantity();

    /**
     * Define amount
     *
     * @param float $amount
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
     */
    public function setAmount($amount);

    /**
     * Retrieve amount
     *
     * @return float
     */
    public function getAmount();

    /**
     * Define VAT percent
     *
     * @param float $vat
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
     */
    public function setVat($vat);

    /**
     * Retrieve VAL percent
     *
     * @return float
     */
    public function getVat();

    /**
     * Define VAT amount
     *
     * @param float $amount
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
     */
    public function setVatAmount($amount);

    /**
     * Retrieve VAT amount
     *
     * @return float
     */
    public function getVatAmount();

    /**
     * Define description
     *
     * @param string $descritpion
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
     */
    public function setDescription($descritpion);

    /**
     * Retrieve description
     *
     * @return string
     */
    public function getDescription();

    /**
     * Define discount line items
     *
     * @param array|\Dintero\Checkout\Api\Data\DiscountInterface[] $discountLines
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface
     */
    public function setDiscountLines(array $discountLines);

    /**
     * Retrieve discount line items
     *
     * @return \Dintero\Checkout\Api\Data\DiscountInterface[]
     */
    public function getDiscountLines();
}
