<?php

namespace Dintero\Checkout\Model\Session;

use Dintero\Checkout\Model\Api\Client;

class Validator
{
    /**
     * @var \Dintero\Checkout\Model\Api\Request\LineIdGenerator $lineIdGenerator
     */
    private $lineIdGenerator;

    /**
     * Define class dependencies
     *
     * @param \Dintero\Checkout\Model\Api\Request\LineIdGenerator $lineIdGenerator
     */
    public function __construct(\Dintero\Checkout\Model\Api\Request\LineIdGenerator $lineIdGenerator)
    {
        $this->lineIdGenerator = $lineIdGenerator;
    }

    /**
     * Validate item
     *
     * @param array $sessionItems
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    private function validateItem($sessionItems, $item)
    {
        $lineId = $this->lineIdGenerator->generate($item);
        foreach ($sessionItems as $sessionItem) {
            if (isset($sessionItem['line_id']) && $sessionItem['line_id'] == $lineId) {
                return $sessionItem['quantity'] == $item->getQty();
            }
        }
        return false;
    }

    /**
     * Validate items
     *
     * @param \Magento\Sales\Model\Order|\Magento\Quote\Model\Quote $salesObject
     * @param \Magento\Framework\DataObject $response
     * @return bool
     */
    public function validateItems($salesObject, $response)
    {
        $sessionItems = $response->getData('order/items');
        foreach ($salesObject->getAllVisibleItems() as $item) {
            if (!$this->validateItem($sessionItems, $item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate
     *
     * @param \Magento\Framework\DataObject $sessionInfo
     * @param \Magento\Sales\Model\Order|\Magento\Quote\Model\Quote $salesObject
     * @return boolean
     */
    public function validate($sessionInfo, $salesObject)
    {
        $incrementId = $salesObject->getReservedOrderId() ?? $salesObject->getIncrementId();
        if (!$sessionInfo->getId()
            || $sessionInfo->getData('order/merchant_reference') != $incrementId) {
            return false;
        }

        $expirationDate = $sessionInfo->getData('expires_at');
        if ($expirationDate && time() > strtotime($expirationDate)) {
            return false;
        }

        $events = $sessionInfo->getEvents();

        $statusList = [Client::STATUS_FAILED, Client::STATUS_DECLINED, Client::STATUS_UNKNOWN, Client::STATUS_CANCELLED];
        if (is_array($events) && in_array(end($events)['name'], $statusList)) {
            return false;
        }

        return $this->validateItems($salesObject, $sessionInfo);
    }
}
