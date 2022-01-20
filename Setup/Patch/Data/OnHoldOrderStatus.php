<?php

namespace Dintero\Checkout\Setup\Patch\Data;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order\StatusFactory;

class OnHoldOrderStatus implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var StatusFactory $statusFactory
     */
    private $statusFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StatusFactory $statusFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusFactory = $statusFactory;
    }

    /**
     * @return OnHoldOrderStatus|void
     */
    public function apply()
    {
        try {
            $status = $this->statusFactory->create()
                ->setData('status', 'dintero_pending_approval')
                ->setData('label', 'Dintero Pending Approval')
                ->save();
            // Secondly, assign the status to state
            $status->assignState(\Magento\Sales\Model\Order::STATE_NEW, false);
        } catch (CouldNotSaveException $e) {

        }
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }
}
