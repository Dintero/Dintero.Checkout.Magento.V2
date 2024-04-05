<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Model\Api\Client;

class Transaction extends \Magento\Framework\DataObject
{
    /**
     * List of failed statuses
     *
     * @var array $failedStatuses
     */
    private $failedStatuses = [
        Client::STATUS_FAILED,
        Client::STATUS_DECLINED,
        Client::STATUS_UNKNOWN,
    ];

    /**
     * Check if transaction is failed
     *
     * @return bool
     */
    public function isFailed()
    {
        return in_array($this->getData('status'), $this->failedStatuses);
    }
}
