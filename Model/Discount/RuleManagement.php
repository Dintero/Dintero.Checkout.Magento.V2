<?php
namespace Dintero\Checkout\Model\Discount;

use Dintero\Checkout\Api\Data\Discount\RuleInterfaceFactory;
use Dintero\Checkout\Api\Data\Discount\RuleInterface;
use Dintero\Checkout\Api\Discount\RuleManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;

class RuleManagement implements RuleManagementInterface
{
    /** @var CollectionFactory $collectionFactory */
    private $collectionFactory;

    /** @var RuleInterfaceFactory $discountRuleFactory */
    private $discountRuleFactory;

    /**
     * Define class dependencies
     *
     * @param CollectionFactory $collectionFactory
     * @param RuleInterfaceFactory $discountRuleFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        RuleInterfaceFactory $discountRuleFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->discountRuleFactory = $discountRuleFactory;
    }

    /**
     * Prase rule ids string to array
     *
     * @param string $ruleIds
     * @return string[]
     */
    private function parseAsArray($ruleIds)
    {
        return array_filter(explode(',', $ruleIds ?? ''));
    }

    /**
     * Detect rule type
     *
     * @param \Magento\Salesrule\Model\Rule $rule
     * @return string
     */
    private function resolveRuleType(\Magento\Salesrule\Model\Rule $rule)
    {
        if ($rule->getSimpleAction() === $rule::CART_FIXED_ACTION) {
            return RuleInterface::TYPE_TOTAL;
        }

        if (count($rule->getActions()->getConditions()->getConditions() > 0)) {
            return RuleInterface::TYPE_ITEM;
        }

        return RuleInterface::TYPE_TOTAL;
    }

    /**
     * Retrieve rule labels
     *
     * @param array $ruleIds
     * @param int $storeId
     * @return \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    private function getRules($ruleIds, $storeId)
    {
        /** @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->joinLeft(
            ['sl' => $collection->getTable('salesrule_label')],
            'sl.rule_id = main_table.rule_id'
        );
        $collection->addFieldToFilter('main_table.rule_id', ['in' => $ruleIds]);
        $collection->addFieldToFilter('store_id', [
            ['eq' => $storeId],
            ['null' => true]
        ]);
        $collection
            ->getSelect()
            ->columns(['rule_id', 'name', 'sl.label', 'simple_action', 'conditions_serialized', 'actions_serialized']);

        return $collection;
    }

    /**
     * Retrieve quote applicable sales rules
     *
     * @param Quote|Order $salesObject
     * @return array|\Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    public function getQuoteApplicableRules($salesObject)
    {
        $ruleIds = $this->parseAsArray($salesObject->getAppliedRuleIds());
        if (empty($ruleIds)) {
            return [];
        }

        /** @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection $salesRules */
        $salesRules = $this->getRules($ruleIds, $salesObject->getStoreId());

        $discountRules = [];

        /** @var \Magento\SalesRule\Model\Rule $rule */
        foreach ($salesRules as $rule) {
            /** @var \Dintero\Checkout\Api\Data\Discount\RuleInterface $discountRule */
            $discountRule = $this->discountRuleFactory->create();
            $discountRule->setId($rule->getRuleId());
            $discountRule->setName($rule->getStoreLabel($salesObject->getStoreId()) ?: $rule->getName());
            $discountRule->setDescription($rule->getDescription() ?? '');
            $discountRule->setRuleType($this->resolveRuleType($rule));
            $discountRules[$rule->getRuleId()] = $discountRule;
        }

        return $discountRules;
    }

    /**
     * Crate rule form order
     *
     * @param Order $order
     * @return RuleInterface
     */
    public function createFromOrder(Order $order)
    {
        return $this->discountRuleFactory->create()
            ->setRuleType(RuleInterface::TYPE_TOTAL)
            ->setDescription(__('Discount'))
            ->setName(__('Discount'))
            ->setId($order->getQuoteId());
    }

    /**
     * Create rule from quote
     *
     * @param Quote $quote
     * @return RuleInterface
     */
    public function createFromQuote(Quote $quote)
    {
        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        return $this->discountRuleFactory->create()
            ->setRuleType(RuleInterface::TYPE_TOTAL)
            ->setName($address->getDiscountDescription() ?? __('Discount'))
            ->setDescription($address->getDiscountDescription() ?? __('Discount'))
            ->setAmount($address->getDiscountAmount())
            ->setId($address->getQuoteId());
    }

    /**
     * Create Discount Rule from Sales Item
     *
     * @param Item|Quote\Item $salesItem
     * @return RuleInterface
     */
    public function createFromItem($salesItem)
    {
        return $this->discountRuleFactory->create()
            ->setRuleType(RuleInterface::TYPE_ITEM)
            ->setName(__('Discount'))
            ->setDescription(__('Discount'))
            ->setAmount($salesItem->getDiscountAmount())
            ->setId($salesItem->getQuoteItemId() ?? $salesItem->getId());
    }
}
