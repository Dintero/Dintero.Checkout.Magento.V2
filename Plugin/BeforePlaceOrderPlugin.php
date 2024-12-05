<?php

namespace Dintero\Checkout\Plugin;

use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\Data\PaymentExtensionInterface;
use Magento\Quote\Api\Data\PaymentExtensionInterfaceFactory;

class BeforePlaceOrderPlugin
{
    /**
     * @var Onepage $onepageCheckout
     */
    protected $onepageCheckout;

    /**
     * @var PaymentExtensionInterfaceFactory $extensionAttributesFactory
     */
    protected $extensionAttributesFactory;

    /** @var RequestInterface $httpRequest */
    protected $httpRequest;

    /**
     * Define class dependencies
     *
     * @param Onepage $onepageCheckout
     * @param PaymentExtensionInterfaceFactory $paymentExtensionInterfaceFactory
     * @param RequestInterface $httpRequest
     */
    public function __construct(
        Onepage $onepageCheckout,
        PaymentExtensionInterfaceFactory $paymentExtensionInterfaceFactory,
        RequestInterface $httpRequest
    ) {
        $this->onepageCheckout = $onepageCheckout;
        $this->extensionAttributesFactory = $paymentExtensionInterfaceFactory;
        $this->httpRequest = $httpRequest;

    }

    /**
     * Before placing
     *
     * @param $subject
     * @return void
     */
    public function beforeExecute(\Dintero\Checkout\Controller\Payment\Place $subject)
    {
        $agreementIds = $this->httpRequest->getParam('extension_attributes')['agreement_ids'] ?? [];
        $quote = $this->onepageCheckout->getQuote();
        if (!$quote || empty($agreementIds)) {
            return;
        }

        /** @var PaymentExtensionInterface $extensionAttributes */
        $extensionAttributes = $quote->getPayment()->getExtensionAttributes() === null ?
            $this->extensionAttributesFactory->create() : $quote->getPayment()->getExtensionAttributes();
        $extensionAttributes->setAgreementIds($agreementIds);
    }
}
