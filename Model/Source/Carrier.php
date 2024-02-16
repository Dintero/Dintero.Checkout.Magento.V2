<?php

namespace Dintero\Checkout\Model\Source;

use Magento\Framework\Config\Data;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Shipping\Model\Config;

/**
 * Dintero Payment Action Dropdown source
 */
class Carrier implements OptionSourceInterface
{
    /**
     * @var \Magento\Shipping\Model\Config $shippingConfig
     */
    protected $shippingConfig;

    /**
     * Define class dependencies
     *
     * @param Config $shippingConfig
     */
    public function __construct(Config $shippingConfig)
    {
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * List of carriers
     *
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [];
        $carriers = $this->shippingConfig->getAllCarriers();
        foreach ($carriers as $carrier) {
            $methods = $carrier->getAllowedMethods();
            if (!empty($methods)) {
                $shippingMethods = [];
                foreach ($methods as $methodCode => $methodName) {
                    $shippingMethods[] = [
                        'label' => $methodName,
                        'value' => $methodCode,
                    ];
                }
                $options[] = [
                    'label' => $carrier->getConfigData('title'),
                    'value' => $shippingMethods,
                ];
                continue;
            }

            $options[] = [
                'label' => $carrier->getConfigData('title'),
                'value' => $carrier->getCarrierCode(),
            ];
        }
        return $options;
    }
}
