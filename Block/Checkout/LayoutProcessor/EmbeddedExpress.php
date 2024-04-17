<?php

namespace Dintero\Checkout\Block\Checkout\LayoutProcessor;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class EmbeddedExpress implements LayoutProcessorInterface
{

    /**
     * @var \Dintero\Checkout\Helper\Config $configHelper
     */
    private $configHelper;

    /**
     * Define class dependencies
     *
     * @param \Dintero\Checkout\Helper\Config $configHelper
     */
    public function __construct(\Dintero\Checkout\Helper\Config $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * Process javascript layout
     *
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout)
    {
        if (!$this->configHelper->isEmbedded()) {
            return $jsLayout;
        }

        if ($this->configHelper->isEmbeddedExpress()) {

            $checkoutElementsConfig = $jsLayout['components']['checkout']['children'];
            unset($checkoutElementsConfig['progressBar']);

            $checkoutElementsConfig['steps'] = [
                'sortOrder' => '1',
                'component' => 'Dintero_Checkout/js/view/popout',
                'displayArea' => 'steps',
            ];

            $jsLayout['components']['checkout']['children'] = $checkoutElementsConfig;
        }

        return $jsLayout;
    }
}
