<?php

namespace Dintero\Checkout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\Template\TransportBuilder;

/**
 * Class Config
 *
 * @package Dintero\Payment\Helper
 */
class Email extends AbstractHelper
{
    /**
     * @var TransportBuilder $transportBuilder
     */
    private $transportBuilder;

    /**
     * @var Config $config
     */
    private $config;

    /**
     * Define class dependencies
     *
     * @param Context $context
     * @param Config $config
     * @param TransportBuilder $transportBuilder
     */
    public function __construct(
        Context $context,
        Config $config,
        TransportBuilder $transportBuilder
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Send email
     *
     * @param string $templateId
     * @param \Magento\Sales\Model\Order $order
     * @param string $paymentLink
     * @return $this
     */
    private function send($templateId, $order, $paymentLink)
    {
        $sender = [
            'name' => $this->config->getSenderName($order->getStoreId()),
            'email' => $this->config->getSenderEmail($order->getStoreId()),
        ];

        $transport = $this->transportBuilder
            ->setTemplateIdentifier($templateId)
            ->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ])
            ->setTemplateVars([
                'order' => $order,
                'link' => $paymentLink,
                'store_name' => $order->getStore()->getName(),
                'days' => $this->config->getSessionExpirationDays()
            ])
            ->setFrom($sender)
            ->addTo([$order->getCustomerEmail()])
            ->getTransport();

        $transport->sendMessage();
        return $this;
    }

    /**
     * Send payment link to customer
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $paymentLink
     * @return void
     */
    public function sendPaymentLink($order, $paymentLink)
    {
        $this->send($this->config->getPaymentLinkTemplate(), $order, $paymentLink);
    }
}
