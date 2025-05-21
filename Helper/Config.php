<?php

namespace Dintero\Checkout\Helper;

use Dintero\Checkout\Model\Api\Client;
use Dintero\Checkout\Model\Dintero;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

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
     * On Hold Order Status
     */
    const XPATH_ON_HOLD_STATUS = 'payment/dintero/order_on_hold_status';

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
     * Logo Type
     */
    const XPATH_LOGO_TYPE = 'payment/dintero/logo_type';

    /*
     * Logo Color
     */
    const XPATH_LOGO_COLOR = 'payment/dintero/logo_color';

    /*
     * Logo width in pixels
     */
    const XPATH_LOGO_WIDTH = 'payment/dintero/logo_width';

    /*
     * Checkout Language
     */
    const XPATH_LANGUAGE = 'payment/dintero/language';

    /*
     * XPATH Embedded checkout enabled
     */
    const XPATH_IS_EMBEDDED = 'payment/dintero/is_embedded';

    /*
     * Embed Type
     */
    const XPATH_EMBED_TYPE = 'payment/dintero/embed_type';

    /*
     * Embed Type
     */
    const XPATH_IS_POPOUT = 'payment/dintero/is_popout';

    /*
     * XPATH Express checkout enabled
     */
    const XPATH_IS_EXPRESS = 'payment/dintero/is_express';

    /*
     * XPATH Express button image
     */
    const XPATH_EXPRESS_BUTTON_IMAGE = 'payment/dintero/express_button_type';

    /*
     * Create Invoice
     */
    const XPATH_CREATE_INVOICE = 'payment/dintero/create_invoice';

    /*
     * Shipping methods map
     */
    const XPATH_PICKUP_METHODS = 'payment/dintero/shipping_methods_map';

    /*
     * Shipping methods map
     */
    const XPATH_UNSPECIFIED_METHODS = 'payment/dintero/unspecified_methods_map';

    /*
     * Allow ship-to-different address
     */
    const XPATH_ALLOW_DIFF_SHIP_ADDR = 'payment/dintero/allow_different_shipping';

    /*
     * Allowed customer types
     */
    const XPATH_ALLOW_CUSTOMER_TYPES = 'payment/dintero/allowed_customer_types';

    /*
     * Default callback delay in seconds
     */
    const DEFAULT_CALLBACK_DELAY = 30;

    /*
     * Default logo width
     */
    const DEFAULT_LOGO_WIDTH = 500;

    /*
     * Default logo color
     */
    const DEFAULT_LOGO_COLOR = '#c4c4c4';

    /*
     * Enable pay button on product page
     */
    const XPATH_PRODUCT_PAGE_BUTTON_ENABLED = 'payment/dintero/product_page_button_enabled';

    /*
     * Enable pay button on minicart and cart
     */
    const XPATH_CART_BUTTON_ENABLED = 'payment/dintero/cart_button_enabled';

    /*
     * Number of days for session expiration
     */
    const XPATH_SESSION_EXP_DAY = 'payment/dintero/expiration_days';

    /*
     * Payment Email Template
     */
    const XPATH_PAYMENT_EMAIL_TPL = 'payment/dintero/payment_email';

    /*
     * Line Id Field
     */
    const XPATH_ID_FIELD = 'payment/dintero/id_field';

    /**
     * Encryptor object used to encrypt/decrypt sensitive data
     *
     * @var EncryptorInterface $encryptor
     */
    private $encryptor;

    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /**
     * Config constructor.
     *
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context               $context,
        EncryptorInterface    $encryptor,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
    }

    /**
     * Checking whether the payment method is active or not
     *
     * @param $store Store|null
     * @return bool
     */
    public function isActive(Store $store = null)
    {
        $store = $store ?? $this->storeManager->getStore();
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
     * @param int|string $scopeCode
     * @return string
     */
    public function getClientId($scopeCode = null)
    {
        return $this->encryptor->decrypt(
            $this->scopeConfig->getValue(self::XPATH_CLIENT_ID, ScopeInterface::SCOPE_STORE, $scopeCode)
        );
    }

    /**
     * Retrieving client secret from configuration
     *
     * @param int|string
     * @return string
     */
    public function getClientSecret($scopeCode = null)
    {
        return $this->encryptor->decrypt(
            $this->scopeConfig->getValue(self::XPATH_CLIENT_SECRET, ScopeInterface::SCOPE_STORE, $scopeCode)
        );
    }

    /**
     * Retrieving account id from configuration
     *
     * @param int|string $scopeCode
     * @return string
     */
    public function getAccountId($scopeCode = null)
    {
        return $this->scopeConfig->getValue(self::XPATH_ACCOUNT_ID, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * Retrieving environment
     *
     * @param int|string $scopeCode
     * @return string
     */
    public function getEnvironment($scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag(self::XPATH_ENVIRONMENT, ScopeInterface::SCOPE_STORE, $scopeCode) ? 'T' : 'P';
    }

    /**
     * Retrieving account id with environment prefix
     *
     * @param int|string $scopeCode
     * @return string
     */
    public function getFullAccountId($scopeCode = null)
    {
        return $this->getEnvironment($scopeCode) . $this->getAccountId($scopeCode);
    }

    /**
     * Retrieving callback url
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->_getUrl('dintero/payment/response', [
            '_query' => [
                'method' => 'POST',
                'delay_callback' => self::DEFAULT_CALLBACK_DELAY,
                'report_error' => 'true',
            ]
        ]);
    }

    /**
     * Retrieving profile id from configuration
     *
     * @param int|string $scopeCode
     * @return string
     */
    public function getProfileId($scopeCode = null)
    {
        return $this->encryptor->decrypt(
            $this->scopeConfig->getValue(self::XPATH_PROFILE_ID, ScopeInterface::SCOPE_STORE, $scopeCode)
        );
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
     * Retrieving invoice pay success url
     *
     * @return string
     */
    public function getInvoicePayUrl()
    {
        return $this->_getUrl('sales/order/history');
    }

    /**
     * Retrieving Shipping url callback
     *
     * @return string
     */
    public function getShippingCallbackUrl($storeCode)
    {
        return $this->_getUrl(sprintf('rest/%s/V1', $storeCode), ['dintero' => 'shipping']);
    }

    /**
     * @return string
     */
    public function getExpressCheckoutCallback($storeCode)
    {
        return $this->_getUrl(
            sprintf('rest/%s/V1', $storeCode),
            [
                'dintero' => 'express',
                '_query' => [
                    'method' => 'POST',
                    'delay_callback' => self::DEFAULT_CALLBACK_DELAY,
                    'report_error' => 'true',
                ]
            ]
        );
    }

    /**
     * @return string
     */
    public function getEmbeddedCheckoutCallback($storeCode)
    {
        return $this->_getUrl(
            sprintf('rest/%s/V1', $storeCode),
            [
                'dintero' => 'embedded',
                '_query' => [
                    'method' => 'POST',
                    'delay_callback' => self::DEFAULT_CALLBACK_DELAY,
                    'report_error' => 'true',
                ]
            ]
        );
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

    /**
     * Retrieving logo type
     *
     * @return string
     */
    public function getLogoType()
    {
        return $this->scopeConfig->isSetFlag(self::XPATH_LOGO_TYPE) ? 'mono' : 'colors';
    }

    /**
     * Retrieving logo color
     *
     * @return string
     */
    public function getLogoColor()
    {
        $value = $this->scopeConfig->getValue(self::XPATH_LOGO_COLOR);
        return $value ?: self::DEFAULT_LOGO_COLOR;
    }

    /**
     * Retrieving logo width
     *
     * @return int
     */
    public function getLogoWidth()
    {
        $value = $this->scopeConfig->getValue(self::XPATH_LOGO_WIDTH);
        return $value ?: self::DEFAULT_LOGO_WIDTH;
    }

    /**
     * Retrieving footer logo url
     *
     * @return string
     */
    public function getFooterLogoUrl()
    {
        return $this->getProfileId() ? $this->getCheckoutLogoUrl() : $this->getDefaultLogoUrl();
    }

    /**
     * Retrieving default logo url
     *
     * @return string
     */
    public function getDefaultLogoUrl()
    {
        $baseUrl = Client::CHECKOUT_API_BASE_URL;
        $pattern = '%s/branding/logos/visa_mastercard_vipps_swish_walley/'
            . 'variant/%s/colors/color/%s/width/%d/dintero_left_frame.svg';

        if ($this->scopeConfig->isSetFlag(self::XPATH_LOGO_TYPE)) {
            $pattern = '%s/branding/logos/visa_mastercard_vipps_swish_walley/'
                . 'variant/%s/color/%s/width/%d/dintero_left_frame.svg';
        }

        return sprintf(
            $pattern,
            $baseUrl,
            $this->getLogoType(),
            str_replace('#', '', $this->getLogoColor()),
            $this->getLogoWidth()
        );
    }

    /**
     * Retrieving checkout logo url
     *
     * @param int|string
     * @return string
     */
    public function getCheckoutLogoUrl($scopeCode = null)
    {
        $baseUrl = Client::CHECKOUT_API_BASE_URL;
        $pattern = '%s/branding/accounts/%s/profiles/%s/'
            . 'variant/%s/color/%s/width/%d/dintero_left_frame.svg';

        return sprintf(
            $pattern,
            $baseUrl,
            $this->getFullAccountId($scopeCode),
            $this->getProfileId($scopeCode),
            $this->getLogoType(),
            str_replace('#', '', $this->getLogoColor()),
            $this->getLogoWidth()
        );
    }

    /**
     * Retrieving language code
     *
     * @param int|string|null
     * @return string
     */
    public function getLanguage($scope = null)
    {
        return str_replace('_', '-', $this->scopeConfig->getValue(
            self::XPATH_LANGUAGE,
            ScopeInterface::SCOPE_STORE,
            $scope
        ));
    }

    /**
     * Resolving checkout url
     *
     * @param array $queryParams
     * @return string
     */
    public function resolveCheckoutUrl($url)
    {
        $queryParams = parse_url($url, PHP_URL_QUERY);
        $queryParams['language'] = $this->getLanguage();
        list($baseUrl) = explode('?', $url);
        return implode('?', [$baseUrl, http_build_query($queryParams)]);
    }

    /**
     * Checking whether embedded checkout is enabled
     *
     * @return bool
     */
    public function isEmbedded()
    {
        return $this->scopeConfig->isSetFlag(self::XPATH_IS_EMBEDDED, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Checking if express checkout is enabled
     *
     * @return bool
     */
    public function isExpress()
    {
        return $this->scopeConfig->isSetFlag(self::XPATH_IS_EXPRESS, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Checking if payment button is enabled for product page
     *
     * @return bool
     */
    public function isProductPagePaymentButtonEnabled()
    {
        return $this->isActive()
            && $this->isExpress()
            && $this->scopeConfig->isSetFlag(self::XPATH_PRODUCT_PAGE_BUTTON_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if cart and minicart buttons should be enabled
     *
     * @return bool
     */
    public function showExpressCartButton()
    {
        return $this->isActive()
            && $this->isExpress()
            && $this->scopeConfig->isSetFlag(self::XPATH_CART_BUTTON_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Retrieving payment action
     *
     * @param int|string $scopeCode
     * @return string
     */
    public function getPaymentAction($scopeCode = null)
    {
        return $this->scopeConfig->getValue(self::XPATH_PAYMENT_ACTION, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * @param int|string $scopeCode
     * @return bool
     */
    public function isAutocaptureEnabled($scopeCode = null)
    {
        return $this->getPaymentAction($scopeCode) == Dintero::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * @param int|string $scopeCode
     * @return string
     */
    public function getExpressButtonImage($scopeCode = null)
    {
        return $this->scopeConfig->getValue(self::XPATH_EXPRESS_BUTTON_IMAGE, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * @param string $scopeCode
     * @return mixed
     */
    public function getOnHoldOrderStatus($scopeCode = null)
    {
        return $this->scopeConfig->getValue(self::XPATH_ON_HOLD_STATUS, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * Retrieve payment link email template
     *
     * @param string $scopeCode
     * @return mixed
     */
    public function getPaymentLinkTemplate($scopeCode = null)
    {
        return $this->scopeConfig->getValue(self::XPATH_PAYMENT_EMAIL_TPL, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * Retrieve sender name
     *
     * @param string $scopeCode
     * @return string
     */
    public function getSenderName($scopeCode = null)
    {
        return $this->scopeConfig->getValue('trans_email/ident_support/name', ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * Retrieve sender email
     *
     * @param string $scopeCode
     * @return string
     */
    public function getSenderEmail($scopeCode = null)
    {
        return $this->scopeConfig->getValue('trans_email/ident_support/email', ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * Retrieve session expiration in days
     *
     * @return mixed
     */
    public function getSessionExpirationDays()
    {
        return $this->scopeConfig->getValue(self::XPATH_SESSION_EXP_DAY);
    }

    /**
     * Retrieve session expiration date time
     *
     * @return integer
     */
    public function getSessionExpirationDate()
    {
        return strtotime(sprintf('+%dday', $this->getSessionExpirationDays()));
    }

    /**
     * @param string|null $scopeCode
     * @return bool
     */
    public function canCreateInvoice($scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag(self::XPATH_CREATE_INVOICE, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * Retrieve pickup methods list
     *
     * @param string $scopeCode
     * @return array
     */
    public function getPickupMethods($scopeCode = null)
    {
        return explode(
            ',',
            $this->scopeConfig->getValue(self::XPATH_PICKUP_METHODS, ScopeInterface::SCOPE_STORE, $scopeCode) ?? ''
        );
    }

    /**
     * Retrieve pickup methods list
     *
     * @param string $scopeCode
     * @return array
     */
    public function getUnspecifiedMethods($scopeCode = null)
    {
        return explode(
            ',',
            $this->scopeConfig->getValue(self::XPATH_UNSPECIFIED_METHODS, ScopeInterface::SCOPE_STORE, $scopeCode) ?? ''
        );
    }

    /**
     * Retrieve line id generation logic key
     *
     * @return string
     */
    public function getLineIdFieldName()
    {
        return $this->scopeConfig->getValue(self::XPATH_ID_FIELD);
    }

    /**
     * Retrieve embed type
     *
     * @param string $scopeCode
     * @return string
     */
    public function getEmbedType($scopeCode = null)
    {
        $embedType = $this->scopeConfig->getValue(self::XPATH_EMBED_TYPE, ScopeInterface::SCOPE_STORE, $scopeCode);
        return $embedType === Client::TYPE_EXPRESS ? Client::TYPE_EXPRESS : Client::TYPE_EMBEDDED;
    }

    /**
     * Check if embedded express is enabled
     *
     * @param string $scopeCode
     * @return bool
     */
    public function isEmbeddedExpress($scopeCode = null)
    {
        return $this->getEmbedType($scopeCode) === Client::TYPE_EXPRESS;
    }

    /**
     * Check if embedded checkout popout mode is enabled
     *
     * @param $scopeCode
     * @return bool
     */
    public function isPopOut($scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag(self::XPATH_IS_POPOUT, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * Allow ship-to-different address
     *
     * @param string $scopeCode
     * @return string[]
     */
    public function getDifferentShippingAddressCustomerTypes($scopeCode = null)
    {
        $value = $this->scopeConfig->getValue(self::XPATH_ALLOW_DIFF_SHIP_ADDR, ScopeInterface::SCOPE_STORE, $scopeCode);
        return $value ? explode(',', $value) : [];
    }

    /**
     * @param $scopeCode
     * @return string[]
     */
    public function getAllowedCustomerTypes($scopeCode = null)
    {
        $value = $this->scopeConfig->getValue(self::XPATH_ALLOW_CUSTOMER_TYPES, ScopeInterface::SCOPE_STORE, $scopeCode);
        return $value ? explode(',', $value) : [];
    }
}
