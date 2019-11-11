<?php

namespace Dintero\Checkout\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Dintero\Checkout\Helper\Config as ConfigHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;


/**
 * Class ConfigProvider
 *
 * @package Dintero\Payment\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Config helper
     *
     * @var ConfigHelper $configHelper
     */
    private $configHelper;

    /**
     * Checkout Session
     *
     * @var Session $session
     */
    private $session;

    /**
     * ConfigProvider constructor.
     *
     * @param ConfigHelper $configHelper
     * @param Session $session
     */
    public function __construct(
        ConfigHelper $configHelper,
        Session $session
    ) {
        $this->configHelper = $configHelper;
        $this->session = $session;
    }

    /**
     * Return payment config for frontend JS to use
     *
     * @return string[][]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        $store = $this->session->getQuote()->getStore();
        $paymentConfig = [
            'payment' => [
                'dintero' => [
                    'messages' => null,
                    'success'           => 0,
                    'enabled'           => $this->configHelper->isActive($store),
                    'placeOrderUrl'     => $this->configHelper->getPlaceOrderUrl(),
                    'logoUrl'           => $this->configHelper->getCheckoutLogoUrl(),
                    'available_methods' => [
                        'type'      => 'dintero',
                        'component' => 'Dintero_Checkout/js/view/payment/method-renderer/dintero'
                    ]
                ]
            ]
        ];

        if (!$this->configHelper->isActive($store)) {
            $paymentConfig['payment']['dintero']['message'] = __('Dintero Payments is not enabled');
            return $paymentConfig;
        }

        return $paymentConfig;
    }
}
