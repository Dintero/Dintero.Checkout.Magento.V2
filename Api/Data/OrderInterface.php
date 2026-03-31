<?php
namespace Dintero\Checkout\Api\Data;

interface OrderInterface
{
    /*
     * Amount
     */
    public const AMOUNT = 'amount';

    /*
     * Currency
     */
    public const CURRENCY = 'currency';

    /*
     * VAT Amount
     */
    public const VAT_AMOUNT = 'vat_amount';

    /*
     * Items
     */
    public const ITEMS = 'items';

    /*
     * Discount codes
     */
    public const DISCOUNT_CODES = 'discount_codes';

    /*
     * Discount lines
     */
    public const DISCOUNT_LINES = 'discount_lines';

    /*
     * Shipping Option
     */
    public const SHIPPING_OPTION = 'shipping_option';

    /**
     * Dfine amount
     *
     * @param float $amount
     * @return \Dintero\Checkout\Api\Data\OrderInterface
     */
    public function setAmount($amount);

    /**
     * Retrieve amount
     *
     * @return float
     */
    public function getAmount();

    /**
     * Define currency
     *
     * @param string $currency
     * @return \Dintero\Checkout\Api\Data\OrderInterface
     */
    public function setCurrency($currency);

    /**
     * Retrieve currency
     *
     * @return string
     */
    public function getCurrency();

    /**
     * Define VAT amount
     *
     * @param float $amount
     * @return \Dintero\Checkout\Api\Data\OrderInterface
     */
    public function setVatAmount($amount);

    /**
     * Retieve VAT amount
     *
     * @return float
     */
    public function getVatAmount();

    /**
     * Define item list
     *
     * @param array $items
     * @return \Dintero\Checkout\Api\Data\OrderInterface
     */
    public function setItems($items);

    /**
     * Retrieve item list
     *
     * @return \Dintero\Checkout\Api\Data\Order\ItemInterface[]
     */
    public function getItems();

    /**
     * Define discount codes
     *
     * @param string[] $discountCodes
     * @return \Dintero\Checkout\Api\Data\OrderInterface
     */
    public function setDiscountCodes(array $discountCodes);

    /**
     * Retrieve discount codes
     *
     * @return string[]
     */
    public function getDiscountCodes();

    /**
     * Define discount lines
     *
     * @param \Dintero\Checkout\Api\Data\DiscountInterface[] $discountLines
     * @return \Dintero\Checkout\Api\Data\OrderInterface
     */
    public function setDiscountLines($discountLines);

    /**
     * Retrieve discount lines
     *
     * @return \Dintero\Checkout\Api\Data\DiscountInterface[]
     */
    public function getDiscountLines();

    /**
     * Define shipping option
     *
     * @param \Dintero\Checkout\Api\Data\ShippingMethodInterface $shippingMethod
     * @return \Dintero\Checkout\Api\Data\OrderInterface
     */
    public function setShippingOption(ShippingMethodInterface $shippingMethod);

    /**
     * Retrieve shipping option
     *
     * @return \Dintero\Checkout\Api\Data\ShippingMethodInterface|null
     */
    public function getShippingOption();
}
