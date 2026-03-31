<?php

namespace Dintero\Checkout\Api\Data\Discount;

interface RuleInterface
{
    /*
     * Rule Id
     */
    public const ID = 'id';

    /*
     * Type
     */
    public const TYPE = 'type';

    /*
     * Item level rule type
     */
    public const TYPE_ITEM = 'item';

    /*
     * Cart level rule type
     */
    public const TYPE_TOTAL = 'total';

    /*
     * Rule name
     */
    public const NAME = 'name';

    /*
     * Rule description
     */
    public const DESCRIPTION = 'description';

    /*
     * Rule Amount
     */
    public const AMOUNT = 'amount';

    /**
     * Define id
     *
     * @param int $id
     * @return RuleInterface
     */
    public function setId(int $id);

    /**
     * Retrieve id
     *
     * @return int|null
     */
    public function getId();

    /**
     * Define rule type
     *
     * @param string $ruleType
     * @return RuleInterface
     */
    public function setRuleType(string $ruleType);

    /**
     * Retrieve rule type
     *
     * @return string
     */
    public function getRuleType();

    /**
     * Define discount rule name
     *
     * @param string $name
     * @return RuleInterface
     */
    public function setName(string $name);

    /**
     * Retrieve discount rule name
     *
     * @return string
     */
    public function getName():string;

    /**
     * Define description
     *
     * @param string $description
     * @return RuleInterface
     */
    public function setDescription(string $description);

    /**
     * Retrieve description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Define amount
     *
     * @param float|int $amount
     * @return RuleInterface
     */
    public function setAmount($amount);

    /**
     * Retrieve amount
     *
     * @return int|float
     */
    public function getAmount();
}
