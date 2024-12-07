<?php

namespace Dintero\Checkout\Model\Agreements;

use Magento\CheckoutAgreements\Model\AgreementsProvider;
use Magento\Store\Model\ScopeInterface;

class Validator
{
    private $scopeConfiguration;

    /**
     * @var \Magento\Checkout\Api\AgreementsValidatorInterface
     */
    private $agreementsValidator;

    /**
     * @var \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface
     */
    private $checkoutAgreementsList;

    /**
     * @var \Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter
     */
    private $activeStoreAgreementsFilter;

    public function __construct(
        \Magento\Checkout\Api\AgreementsValidatorInterface $agreementsValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration,
        \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface $checkoutAgreementsList,
        \Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter $activeStoreAgreementsFilter,
    ) {
        $this->agreementsValidator = $agreementsValidator;
        $this->scopeConfiguration = $scopeConfiguration;
        $this->checkoutAgreementsList = $checkoutAgreementsList;
        $this->activeStoreAgreementsFilter = $activeStoreAgreementsFilter;
    }

    /**
     * Validate agreements
     *
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @return bool
     */
    protected function validateAgreements(\Magento\Quote\Api\Data\PaymentInterface $paymentMethod)
    {
        $agreements = $paymentMethod->getExtensionAttributes() === null
            ? []
            : $paymentMethod->getExtensionAttributes()->getAgreementIds();
        if (empty($agreements)) {
            $agreements = $paymentMethod->getAdditionalInformation('agreement_ids');
        }
        return $this->agreementsValidator->isValid($agreements);
    }

    /**
     * Verify if agreement validation needed.
     *
     * @return bool
     */
    private function isAgreementEnabled()
    {
        $isAgreementsEnabled = $this->scopeConfiguration->isSetFlag(
            AgreementsProvider::PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        $agreementsList = $isAgreementsEnabled
            ? $this->checkoutAgreementsList->getList($this->activeStoreAgreementsFilter->buildSearchCriteria())
            : [];
        return (bool) ($isAgreementsEnabled && count($agreementsList) > 0);
    }

    /**
     * Validate agreements
     *
     * @return bool
     */
    public function validate(\Magento\Quote\Api\Data\PaymentInterface $paymentMethod)
    {
        if ($this->isAgreementEnabled()) {
            return $this->validateAgreements($paymentMethod);
        }
        return true;
    }
}
