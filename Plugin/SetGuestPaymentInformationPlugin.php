<?php

namespace Dintero\Checkout\Plugin;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\Data\PaymentExtensionInterface;
use Magento\Quote\Api\Data\PaymentExtensionInterfaceFactory;

class SetGuestPaymentInformationPlugin
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
     * Before saving payment information logic
     *
     * @param GuestPaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $agreementIds = $paymentMethod->getExtensionAttributes() !== null
            ? $paymentMethod->getExtensionAttributes()->getAgreementIds() : [];
        $quote = $this->onepageCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation('agreement_ids', $agreementIds);
        return [$cartId, $email, $paymentMethod, $billingAddress];
    }
}
