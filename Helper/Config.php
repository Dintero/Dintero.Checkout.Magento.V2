<?php

namespace Dintero\Hp\Helper;

use Dintero\Hp\Model\Dintero;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\Store;

/**
 * Class Config
 *
 * @package Dintero\Payment\Helper
 */
class Config extends AbstractHelper
{
    /*
     * XPATH Check if the payment method is active
     */
    const XPATH_IS_ACTIVE = 'payment/dintero/active';

    /*
     * XPATH for client id
     */
    const XPATH_CLIENT_ID = 'payment/dintero/client_id';

    /*
     * XPATH for client secret
     */
    const XPATH_CLIENT_SECRET = 'payment/dintero/client_secret';

    /*
     * XPATH for account id
     */
    const XPATH_ACCOUNT_ID = 'payment/dintero/account_id';

    /*
     * XPATH for environment
     */
    const XPATH_ENVIRONMENT = 'payment/dintero/environment';

    /*
     * XPATH for profile id
     */
    const XPATH_PROFILE_ID = 'payment/dintero/checkout_profile_id';

    /*
     * Payment action
     */
    const XPATH_PAYMENT_ACTION = 'payment/dintero/payment_action';

    /*
     * Precision XPATH
     */
    const XPATH_PRECISION = 'payment/dintero/precision';

    /**
     * Encryptor object used to encrypt/decrypt sensitive data
     *
     * @var EncryptorInterface $encryptor
     */
    private $encryptor;

    /**
     * Config constructor.
     *
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(Context $context, EncryptorInterface $encryptor)
    {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /**
     * Checking whether the payment method is active or not
     *
     * @param $store Store
     * @return bool
     */
    public function isActive(Store $store)
    {
        return $this->scopeConfig->isSetFlag(self::XPATH_IS_ACTIVE, $store->getScopeType());
    }

    /**
     * Retrieving payment session url
     *
     * @return string
     */
    public function getPlaceOrderUrl()
    {
        return $this->_getUrl('dintero/payment/place');
    }

    /**
     * Retrieving client id from configuration
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(self::XPATH_CLIENT_ID));
    }

    /**
     * Retrieving client secret from configuration
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(self::XPATH_CLIENT_SECRET));
    }

    /**
     * Retrieving account id from configuration
     *
     * @return string
     */
    public function getAccountId()
    {
        return $this->scopeConfig->getValue(self::XPATH_ACCOUNT_ID);
    }

    /**
     * Retrieving environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->scopeConfig->isSetFlag(self::XPATH_ENVIRONMENT) ? 'T' : 'P';
    }

    /**
     * Retrieving account id with environment prefix
     *
     * @return string
     */
    public function getFullAccountId()
    {
        return $this->getEnvironment() . $this->getAccountId();
    }

    /**
     * Retrieving callback url
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->_getUrl('dintero/payment/response');
    }

    /**
     * Retrieving profile id from configuration
     *
     * @return string
     */
    public function getProfileId()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(self::XPATH_PROFILE_ID));
    }

    /**
     * Retrieving return url
     *
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->_getUrl('dintero/payment/success');
    }

    /**
     * Can auto-capture
     *
     * @return bool
     */
    public function canAutoCapture()
    {
        return $this->scopeConfig->getValue(self::XPATH_PAYMENT_ACTION) == Dintero::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * Retrieving invoice pay success url
     *
     * @return string
     */
    public function getInvoicePayUrl()
    {
        return $this->_getUrl('sales/order/history');
    }

    /**
     * Invoice call back url
     *
     * @param Invoice $invoice
     * @return string
     */
    public function getInvoiceCallBackUrl(Invoice $invoice)
    {
        return $this->_getUrl('dintero/invoice/response', ['invoice_id' => $invoice->getId()]);
    }
}
