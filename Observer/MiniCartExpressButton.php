<?php
namespace Dintero\Checkout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Dintero\Checkout\Block\Express;

/**
 * Class MiniCartExpressButton
 *
 * @package Dintero\Checkout\Observer
 */
class MiniCartExpressButton implements ObserverInterface
{

    /**
     * @var \Dintero\Checkout\Helper\Config
     */
    protected $configHelper;

    /**
     * MiniCartExpressButton constructor.
     *
     * @param \Dintero\Checkout\Helper\Config $configHelper
     */
    public function __construct(\Dintero\Checkout\Helper\Config $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isExpress() || !$this->configHelper->isProductPagePaymentButtonEnabled()) {
            return;
        }
        /** @var \Magento\Catalog\Block\ShortcutButtons $container */
        $container = $observer->getContainer();
        $expressButton = $container->getLayout()->createBlock(Express::class, 'dintero.express');
        $container->addShortcut(
            $expressButton->setTemplate('Dintero_Checkout::product/payment-link-product-page.phtml')
        );
    }
}
