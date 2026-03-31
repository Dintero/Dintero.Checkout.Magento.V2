<?php

namespace Dintero\Checkout\Model\Discount;

use Dintero\Checkout\Api\Data\Discount\RuleInterface;

class Rule implements RuleInterface
{
    /**
     * @var array $data
     */
    private $data = [];

    /**
     * Populate a value into data array
     *
     * @param string $key
     * @param string $val
     * @return $this
     */
    private function set(string $key, string $val)
    {
        $this->data[$key] = $val;
        return $this;
    }

    /**
     * Retrieve value by key name
     *
     * @param string $key
     * @return mixed|null
     */
    private function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Define id
     *
     * @param int $id
     * @return $this|RuleInterface
     */
    public function setId(int $id)
    {
        return $this->set(self::ID, $id);
    }

    /**
     * Retrieve id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->get(self::ID);
    }

    /**
     * Define rule type
     *
     * @param string $ruleType
     * @return $this|RuleInterface
     */
    public function setRuleType(string $ruleType)
    {
        return $this->set(self::TYPE, $ruleType);
    }

    /**
     * Retrieve rule type
     *
     * @return string
     */
    public function getRuleType(): string
    {
        return $this->get(self::TYPE);
    }

    /**
     * Define rule name
     *
     * @param string $name
     * @return $this|RuleInterface
     */
    public function setName(string $name)
    {
        return $this->set(self::NAME, $name);
    }

    /**
     * Retrieve rule name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->get(self::NAME);
    }

    /**
     * Define description
     *
     * @param string $description
     * @return $this|RuleInterface
     */
    public function setDescription(string $description)
    {
        return $this->set(self::DESCRIPTION, $description);
    }

    /**
     * Retrieve description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->get(self::DESCRIPTION);
    }

    /**
     * Define amount
     *
     * @param float $amount
     * @return $this|RuleInterface
     */
    public function setAmount($amount)
    {
        return $this->set(self::AMOUNT, $amount);
    }

    /**
     * Retrieve amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->get(self::AMOUNT);
    }
}
