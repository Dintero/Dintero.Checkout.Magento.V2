<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Api\Data\ItemInterface;
use Dintero\Checkout\Api\Data\DiscountInterface;

class Item implements ItemInterface
{

    /**
     * @var \Magento\Framework\DataObject $dataObject
     */
    protected $dataObject;

    /**
     * Define class dependencies
     *
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        array $data = []
    ) {
        $this->dataObject = new \Magento\Framework\DataObject($data);
    }

    /**
     * Define data
     *
     * @param string $key
     * @param mixed $value
     * @return \Magento\Framework\DataObject
     */
    protected function setData($key, $value)
    {
        return $this->dataObject->setData($key, $value);
    }

    /**
     * Retrieve data by key
     *
     * @param string $key
     * @return array|mixed|null
     */
    protected function getData($key)
    {
        return $this->dataObject->getData($key);
    }

    /**
     * Define Id
     *
     * @param string $id
     * @return ItemInterface
     */
    public function setId($id)
    {
        $this->setData(self::ID, $id);
        return $this;
    }

    /**
     * Define VAT percent
     *
     * @param float $vat
     * @return ItemInterface
     */
    public function setVat($vat)
    {
        $this->setData(self::VAT, $vat);
        return $this;
    }

    /**
     * Define amount
     *
     * @param float $amount
     * @return ItemInterface
     */
    public function setAmount($amount)
    {
        $this->setData(self::AMOUNT, $amount);
        return $this;
    }

    /**
     * Define line id
     *
     * @param string $lineId
     * @return ItemInterface
     */
    public function setLineId($lineId)
    {
        $this->setData(self::LINE_ID, $lineId);
        return $this;
    }

    /**
     * Define quantity
     *
     * @param integer $qty
     * @return ItemInterface
     */
    public function setQuantity($qty)
    {
        $this->setData(self::QUANTITY, $qty);
        return $this;
    }

    /**
     * Define VAT amount
     *
     * @param float $amount
     * @return ItemInterface
     */
    public function setVatAmount($amount)
    {
        $this->setData(self::VAT_AMOUNT, $amount);
        return $this;
    }

    /**
     * Define description
     *
     * @param string $description
     * @return ItemInterface
     */
    public function setDescription($description)
    {
        $this->setData(self::DESCRIPTION, $description);
        return $this;
    }

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
     * Retrieve VAT percent
     *
     * @return float
     */
    public function getVat()
    {
        return $this->getData(self::VAT);
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
     * Retrieve line id
     *
     * @return string
     */
    public function getLineId()
    {
        return $this->getData(self::LINE_ID);
    }

    /**
     * Retrieve quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->getData(self::QUANTITY);
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
     * Retrieve description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Define discount lines
     *
     * @param DiscountInterface[]|array $discountLines
     * @return ItemInterface
     */
    public function setDiscountLines($discountLines)
    {
        $this->setData(self::DISCOUNT_LINES, $discountLines);
        return $this;
    }

    /**
     * Retrieve discount lines
     *
     * @return array|DiscountInterface[]
     */
    public function getDiscountLines()
    {
        return $this->getData(self::DISCOUNT_LINES) ?? [];
    }
}
