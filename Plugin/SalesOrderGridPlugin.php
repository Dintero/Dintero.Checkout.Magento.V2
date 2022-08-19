<?php

namespace Dintero\Checkout\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

class SalesOrderGridPlugin
{
    /**
     * Before loading sales order grid collection
     *
     * @param Collection $subject
     * @return null
     * @throws LocalizedException
     */
    public function beforeLoad(Collection $subject)
    {
        if (!$subject->isLoaded()) {
            $primaryKey = $subject->getResource()->getIdFieldName();
            $tableName = $subject->getResource()->getTable('sales_order_payment');

            $subject->getSelect()->joinLeft(
                $tableName,
                $tableName . '.parent_id = main_table.' . $primaryKey,
                $tableName . '.dintero_payment_product'
            );
        }

        return null;
    }
}
