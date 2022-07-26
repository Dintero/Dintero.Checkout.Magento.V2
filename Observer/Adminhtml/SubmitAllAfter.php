<?php

namespace Dintero\Checkout\Observer\Adminhtml;

use Dintero\Checkout\Helper\Config;
use Dintero\Checkout\Helper\Email;
use Dintero\Checkout\Model\Api\ClientFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class SubmitAllAfter implements ObserverInterface
{
    /**
     * @var Email $emailHelper
     */
    private $emailHelper;

    private $config;

    /**
     * @var ClientFactory $clientFactory
     */
    private $clientFactory;

    /**
     * Define class dependencies
     *
     * @param Email $emailHelper
     * @param ClientFactory $clientFactory
     * @param Config $config
     */
    public function __construct(
        Email $emailHelper,
        ClientFactory $clientFactory,
        Config $config
    ) {
        $this->emailHelper = $emailHelper;
        $this->clientFactory = $clientFactory;
        $this->config = $config;
    }

    /**
     * Send payment link to customer
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();
        if ($order->getPayment()->getMethod() !== \Dintero\Checkout\Model\Dintero::METHOD_CODE) {
            return;
        }

        $order->setSessionExpiresAt($this->config->getSessionExpirationDate());

        /** @var \Dintero\Checkout\Model\Api\Client $client */
        $client = $this->clientFactory->create();
        $response = $client->initCheckout($order);

        if (empty($response['url'])) {
            throw new LocalizedException(__('Could not generate payment link.'));
        }
        $order->getPayment()->setAdditionalInformation('payment_link', $response['url'])->save();
        $this->emailHelper->sendPaymentLink($order, $response['url']);
    }
}
