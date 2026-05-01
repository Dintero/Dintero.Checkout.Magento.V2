<?php

namespace Dintero\Checkout\Model\Api;

use Dintero\Checkout\Api\Data\DiscountInterface;
use Dintero\Checkout\Api\Data\ShippingMethodInterface;
use Dintero\Checkout\Helper\Config as ConfigHelper;
use Dintero\Checkout\Api\ShippingManagementInterface;
use Dintero\Checkout\Model\Api\Request\Builder\GiftCardItemBuilder;
use Dintero\Checkout\Model\Api\Request\LineIdGenerator;
use Dintero\Checkout\Model\Gateway\Http\Client as DinteroHpClient;
use Dintero\Checkout\Model\Payment\Token;
use Dintero\Checkout\Model\Payment\TokenFactory;
use Dintero\Checkout\Api\Discount\RuleManagementInterface;
use Dintero\Checkout\Api\Data\ItemInterface;
use Dintero\Checkout\Model\Api\Request\Builder\OrderItemBuilder;
use Dintero\Checkout\Model\Api\Request\Builder\DiscountLineBuilder;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\TransferBuilderFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\Order;
use Magento\Framework\Reflection\DataObjectProcessor as ObjectConverter;
use Psr\Log\LoggerInterface;

class Client
{
    /*
     * Dintero api endpoint
     */
    public const API_BASE_URL = 'https://api.dintero.com/v1';

    /*
     * Checkout api endpoint
     */
    public const CHECKOUT_API_BASE_URL = 'https://checkout.dintero.com/v1';

    /*
     * Status captured
     */
    public const STATUS_CAPTURED = 'CAPTURED';

    /*
     * Status authorized
     */
    public const STATUS_AUTHORIZED = 'AUTHORIZED';

    /*
     * Status on hold
     */
    public const STATUS_ON_HOLD = 'ON_HOLD';

    /*
     * Status failed
     */
    public const STATUS_FAILED = 'FAILED';

    /*
     * Status partially captured
     */
    public const STATUS_PARTIALLY_CAPTURED = 'PARTIALLY_CAPTURED';

    /*
     * Status declined
     */
    public const STATUS_DECLINED = 'DECLINED';

    /*
     * Status unknown
     */
    public const STATUS_UNKNOWN = 'UNKNOWN';

    /*
     * Status cancelled
     */
    public const STATUS_CANCELLED = 'CANCELLED';

    /*
     * Status completed
     */
    public const STATUS_COMPLETED = 'COMPLETED';

    /*
     * Standard
     */
    public const TYPE_STANDARD = 'standard';

    /*
     * Express
     */
    public const TYPE_EXPRESS = 'express';

    /*
     * Embedded
     */
    public const TYPE_EMBEDDED = 'embedded';

    /** @var DinteroHpClient $client */
    private $client;

    /** @var ConfigHelper $configHelper */
    private $configHelper;

    /** @var TransferBuilderFactory $transferBuilderFactory */
    private $transferBuilderFactory;

    /** @var TokenFactory $tokenFactory */
    private $tokenFactory;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * JSON Converter
     *
     * @var Json $converter
     */
    private $converter;

    /**
     * @var string $type
     */
    private $type;

    /**
     * @var null|int|string $scope
     */
    private $scope = null;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     */
    protected $quoteResource;

    /**
     * @var \Magento\Quote\Model\QuoteRepository $quoteRepository
     */
    protected $quoteRepository;

    /**
     * @var ObjectManagerInterface $objectManager
     */
    protected $objectManager;

    /**
     * @var LineIdGenerator $lineIdGenerator
     */
    protected $lineIdGenerator;

    /**
     * @var RuleManagementInterface $discountRuleManagement
     */
    protected $discountRuleManagement;

    /**
     * @var OrderItemBuilder $orderItemBuilder
     */
    protected $orderItemBuilder;

    /**
     * @var DiscountLineBuilder $discountLineBuilder
     */
    protected $discountLineBuilder;

    /**
     * @var ObjectConverter $objectConverter
     */
    protected $objectConverter;

    /**
     * @var ShippingManagementInterface $shippingOptionManagement
     */
    protected $shippingOptionManagement;

    /**
     * @var GiftCardItemBuilder $giftCardItemBuilder
     */
    protected $giftCardItemBuilder;

    /**
     * Client constructor.
     *
     * @param DinteroHpClient $client
     * @param ConfigHelper $configHelper
     * @param TransferBuilderFactory $transferBuilderFactory
     * @param TokenFactory $tokenFactory
     * @param LoggerInterface $logger
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param CartRepositoryInterface $quoteRepository
     * @param ObjectManagerInterface $objectManager
     * @param LineIdGenerator $lineIdGenerator
     * @param RuleManagementInterface $ruleManagement
     * @param OrderItemBuilder $orderItemBuilder
     * @param DiscountLineBuilder $discountLineBuilder
     * @param ObjectConverter $objectConverter
     * @param ShippingManagementInterface $shippingOptionManagement
     * @param GiftCardItemBuilder $giftCardItemBuilder
     */
    public function __construct(
        DinteroHpClient                             $client,
        ConfigHelper                                $configHelper,
        TransferBuilderFactory                      $transferBuilderFactory,
        TokenFactory                                $tokenFactory,
        LoggerInterface                             $logger,
        \Magento\Quote\Model\ResourceModel\Quote    $quoteResource,
        CartRepositoryInterface                     $quoteRepository,
        ObjectManagerInterface                      $objectManager,
        LineIdGenerator                             $lineIdGenerator,
        RuleManagementInterface                     $ruleManagement,
        OrderItemBuilder                            $orderItemBuilder,
        DiscountLineBuilder                         $discountLineBuilder,
        ObjectConverter                             $objectConverter,
        ShippingManagementInterface                 $shippingOptionManagement,
        GiftCardItemBuilder                         $giftCardItemBuilder
    ) {
        $this->client = $client;
        $this->configHelper = $configHelper;
        $this->transferBuilderFactory = $transferBuilderFactory;
        $this->tokenFactory = $tokenFactory;
        $this->logger = $logger;
        $this->quoteResource = $quoteResource;
        $this->quoteRepository = $quoteRepository;
        $this->type = self::TYPE_STANDARD;
        $this->objectManager = $objectManager;
        $this->lineIdGenerator = $lineIdGenerator;
        $this->discountRuleManagement = $ruleManagement;
        $this->orderItemBuilder = $orderItemBuilder;
        $this->discountLineBuilder = $discountLineBuilder;
        $this->objectConverter = $objectConverter;
        $this->shippingOptionManagement = $shippingOptionManagement;
        $this->giftCardItemBuilder = $giftCardItemBuilder;
    }

    /**
     * Define checkout type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Retrieve checkout type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type ?? self::TYPE_STANDARD;
    }

    /**
     * Retrieve callback URI
     *
     * @param string|null $storeCode
     * @return string
     */
    protected function getCallbackUrl($storeCode = null)
    {
        if ($this->isExpress()) {
            return $this->configHelper->getExpressCheckoutCallback($storeCode);
        }

        if ($this->isEmbedded()) {
            return $this->configHelper->getEmbeddedCheckoutCallback($storeCode);
        }

        return $this->configHelper->getCallbackUrl();
    }

    /**
     * Retrieving actual version of Magento
     *
     * @return ProductMetadata
     */
    private function getSystemMeta()
    {
        return $this->objectManager->get(ProductMetadata::class);
    }

    /**
     * Building api endpoint
     *
     * @param string $endpoint
     * @return string
     */
    private function getApiUri($endpoint)
    {
        return rtrim(self::API_BASE_URL, '/') . '/' . trim($endpoint, '/');
    }

    /**
     * Building checkout api uri
     *
     * @param string $endpoint
     * @return string
     */
    private function getCheckoutApiUri($endpoint)
    {
        return rtrim(self::CHECKOUT_API_BASE_URL, '/') . '/' . trim($endpoint, '/');
    }

    /**
     * Initializing request
     *
     * @param string $endpoint
     * @param Token|null $token
     * @return \Magento\Payment\Gateway\Http\TransferBuilder
     */
    private function initRequest($endpoint, $token = null)
    {
        $defaultHeaders = [
            'Content-type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
            'Dintero-System-Name' => __('Magento'),
            'Dintero-System-Version' => $this->getSystemMeta()->getVersion(),
            'Dintero-System-Plugin-Name' => 'Dintero.Checkout.Magento.V2',
            'Dintero-System-Plugin-Version' => '1.8.27',
        ];

        if ($token && $token instanceof Token) {
            $defaultHeaders['Authorization'] = $token->getTokenType() . ' ' . $token->getToken();
        }

        return $this->transferBuilderFactory->create()
            ->setUri($endpoint)
            ->setHeaders($defaultHeaders)
            ->shouldEncode(false)
            ->setMethod(DinteroHpClient::METHOD_POST)
            ->setClientConfig(['timeout' => 30]);
    }

    /**
     * Retrieving metadata
     *
     * @return array
     */
    private function getMetaData()
    {
        return [
            'system_x_id' => $this->getSystemMeta()->getName() . ' ' . $this->getSystemMeta()->getEdition(),
            'number_x' => $this->getSystemMeta()->getVersion(),
        ];
    }

    /**
     * Check if current checout type is express
     *
     * @return bool
     */
    private function isExpress()
    {
        return $this->getType() === self::TYPE_EXPRESS;
    }

    /**
     * Check if embedded checkout is being used
     *
     * @return bool
     */
    private function isEmbedded()
    {
        return $this->getType() === self::TYPE_EMBEDDED && $this->configHelper->isEmbedded();
    }

    /**
     * Initialize checkout
     *
     * @param Order $order
     * @return array
     * @throws ClientException
     * @throws ConverterException
     */
    public function initCheckout(Order $order)
    {
        $this->scope = $order->getStoreId();
        $request = $this->initRequest(
            $this->getCheckoutApiUri('sessions-profile'),
            $this->getToken()
        )->setBody($this->prepareData($order, null));

        return $this->client->placeRequest($request->build());
    }

    /**
     * Initialize dintero session from a sales object
     *
     * @param \Magento\Sales\Model\Order|\Magento\Quote\Model\Quote $salesObject
     * @return array|bool|float|int|mixed|string|null
     * @throws ClientException
     * @throws ConverterException
     */
    private function initSession($salesObject)
    {
        $request = $this->initRequest(
            $this->getCheckoutApiUri('sessions-profile'),
            $this->getToken()
        )->setBody($this->prepareData($salesObject, null));
        return $this->client->placeRequest($request->build());
    }

    /**
     * Updating session
     *
     * @param string $sessionId
     * @param \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote $quote
     * @return array|bool|float|int|mixed|string|null
     * @throws ConverterException
     */
    public function updateSession($sessionId, $quote)
    {
        $baseGrandTotal = $quote->getBaseGrandTotal() * 100;

        if ($this->isExpress() && !$quote->getIsVirtual()) {
            $baseShippingAmount = $quote->getShippingAddress()->getBaseShippingAmount() * 100;
            $baseGrandTotal -= $baseShippingAmount;
        }

        $requestData = [
            'remove_lock' => true,
            'order' => [
                'amount' => $baseGrandTotal,
                'currency' => $quote->getBaseCurrencyCode(),
                'merchant_reference' => $quote->getReservedOrderId(),
                'items' => $this->prepareItems($quote),
            ]
        ];

        $customerEmail = $quote->getCustomerEmail()
            ? $quote->getCustomerEmail() : $quote->getBillingAddress()->getEmail();

        if ($quote->getCustomer()) {
            $customerEmail = $quote->getCustomer()->getEmail();
        }

        if (!empty($customerEmail)) {
            $requestData['customer']['email'] = $customerEmail;
        }

        if (!$this->isExpress() && $quote->getBillingAddress()) {
            $requestData['order']['billing_address'] = array_filter($this->prepareAddress($quote->getBillingAddress()));
        }

        if ($this->isExpress() && $quote->getShippingAddress()->getShippingMethod()) {
            $requestData['order']['shipping_option'] = $this->objectConverter->buildOutputDataArray(
                $this->shippingOptionManagement->getSelectedShippingOptionByQuote($quote),
                ShippingMethodInterface::class
            );
        }

        $request = $this->initRequest(
            $this->getCheckoutApiUri(sprintf('sessions/%s', $sessionId)),
            $this->getToken()
        )->setBody($requestData)
            ->setMethod(DinteroHpClient::METHOD_PUT);

        return $this->client->placeRequest($request->build());
    }

    /**
     * Initialize Dintero session from quote
     *
     * @param Quote $quote
     * @return array|bool|float|int|mixed|string|null
     * @throws ClientException
     * @throws ConverterException
     */
    public function initSessionFromQuote(Quote $quote)
    {
        $quote->setDinteroGeneratorCode($this->configHelper->getLineIdFieldName());

        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId();
        }

        $this->quoteResource->save($quote);

        return $this->initSession($quote);
    }

    /**
     * Retrieving token
     *
     * @return Token
     * @throws \Exception
     */
    private function getToken()
    {
        /** @var \Dintero\Checkout\Model\Payment\Token $token */
        $token = $this->tokenFactory->create(['data' => $this->getAccessToken()]);
        if (!$token->getToken()) {
            throw new \Exception(__('Failed to get access token'));
        }
        return $token;
    }

    /**
     * Retrieving access token
     *
     * @return array
     * @throws \Exception
     */
    private function getAccessToken()
    {
        $accountsUrl = $this->getApiUri(sprintf('accounts/%s', $this->configHelper->getFullAccountId($this->scope)));
        $accessTokenUrl = $this->getApiUri(
            sprintf('accounts/%s/auth/token', $this->configHelper->getFullAccountId($this->scope))
        );

        $request = $this->initRequest($accessTokenUrl)
            ->setAuthUsername($this->configHelper->getClientId($this->scope))
            ->setAuthPassword($this->configHelper->getClientSecret($this->scope))
            ->setBody([
                'grant_type' => 'client_credentials',
                'audience' => $accountsUrl
            ]);

        try {

            $response = $this->client->placeRequest($request->build());

            if (!isset($response['access_token'])) {
                throw new \Exception(__('Could not retrieve the access token'));
            }

            return $response;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return [];
    }

    /**
     * Sanitizing phone number
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function sanitizePhoneNumber($phoneNumber)
    {
        if (!isset($phoneNumber)) {
            return $phoneNumber;
        }

        $phoneNumber = trim($phoneNumber);
        $sanitized = preg_replace('/\D/', '', $phoneNumber);
        if (strpos($phoneNumber, '+') === 0) {
            $sanitized = '+' . $sanitized;
        }
        return $sanitized;
    }

    /**
     * Extract default address
     *
     * @param \Magento\Customer\Api\Data\AddressInterface[] $customerAddresses
     * @param string $addressType
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    protected function _extractDefaultAddress($customerAddresses, $addressType = 'default_billing')
    {
        /** @var \Magento\Customer\Api\Data\AddressInterface $address */
        foreach ($customerAddresses as $address) {
            if ($addressType === 'default_billing' && $address->isDefaultBilling()
                || $addressType === 'default_shipping' && $address->isDefaultShipping()
            ) {
                return $address;
            }
        }
        return null;
    }

    /**
     * Retrieve discount holder model
     *
     * @param Quote|Order $salesObject
     * @return Quote\Address|mixed
     */
    protected function createDiscountRule($salesObject)
    {

        if ($salesObject instanceof Quote) {
            return $this->discountRuleManagement->createFromQuote($salesObject);
        }

        if ($salesObject instanceof Order) {
            return $this->discountRuleManagement->createFromOrder($salesObject);
        }
        return null;
    }

    /**
     * Preparing data for submission
     *
     * @param Order|\Magento\Quote\Model\Quote $salesObject
     * @param AbstractModel|null $salesDocument
     * @return array
     */
    private function prepareData($salesObject, $salesDocument = null)
    {

        $customer = !$salesObject->getCustomerIsGuest() && $salesObject->getCustomerId()
            ? $salesObject->getCustomer() : null;

        $customerEmail = $salesObject->getCustomerIsGuest() ?
            $salesObject->getBillingAddress()->getEmail() :
            $salesObject->getCustomerEmail();
        $baseOrderTotal = $salesDocument ? $salesDocument->getBaseGrandTotal() : $salesObject->getBaseGrandTotal();

        if ($this->isExpress() && !$salesObject->getIsVirtual()) {
            $baseShippingAmount = $salesObject->getShippingAddress()->getBaseShippingAmount();
            $baseOrderTotal -= $baseShippingAmount;
        }

        $orderData = [
            'profile_id' => $this->configHelper->getProfileId($this->scope),
            'expires_at' => date(
                'Y-m-d\TH:i:s.z\Z',
                $salesObject->getSessionExpiresAt() ?: strtotime('+4hour')
            ),
            'url' => [
                'return_url' => $this->configHelper->getReturnUrl(),
                'callback_url' => $this->getCallbackUrl($salesObject->getStore()->getCode()),
            ],
            'order' => [
                'amount' => $baseOrderTotal * 100,
                'currency' => $salesObject->getBaseCurrencyCode(),
                'merchant_reference' => $salesObject->getReservedOrderId() ?? $salesObject->getIncrementId(),
                'items' => $this->prepareItems($salesObject),
            ],
        ];

        $canAddAddress = !empty($salesObject->getBillingAddress()->getTelephone())
            && !empty($salesObject->getBillingAddress()->getPostcode());

        if ($canAddAddress) {
            $orderData['customer'] = [
                'phone_number' => $salesObject->getBillingAddress()->getTelephone()
            ];
            $orderData['order']['billing_address'] = $this->prepareAddress($salesObject->getBillingAddress());
        }

        if ($this->isExpress()) {
            $orderData['express']['customer_types'] = $this->configHelper->getAllowedCustomerTypes(
                $salesObject->getStore()->getCode()
            );

            if ($salesObject->getIsVirtual()) {
                $orderData['express']['shipping_mode'] = 'shipping_not_required';
            }

            $orderData['express']['shipping_address_callback_url'] = $this->configHelper->getShippingCallbackUrl(
                $salesObject->getStore()->getCode()
            );
            $orderData['express']['shipping_options'] = [];

            $allowDiffShipCustomerTypes = $this->configHelper->getDifferentShippingAddressCustomerTypes(
                $salesObject->getStore()->getCode()
            );

            if (!empty($allowDiffShipCustomerTypes)) {
                $orderData['configuration']['allow_different_billing_shipping_address'] = $allowDiffShipCustomerTypes;
            }

            if ($agreements = $this->getAgreements()) {
                /** @var \Magento\CheckoutAgreements\Api\Data\AgreementInterface $agreement */
                foreach ($agreements as $agreement) {
                    $orderData['checkboxes'][] = [
                        'id' => $agreement->getAgreementId(),
                        'label' => strip_tags($agreement->getCheckboxText()),
                        'checked' => !$agreement->getMode(),
                        'required' => true
                    ];
                }
            }

            $orderData['configuration']['discounts']['express_discount_codes']['enabled'] = true;
            if ($salesObject->getCouponCode()) {
                $orderData['discount_codes'] = [$salesObject->getCouponCode()];
            }

            $orderData['express']['discount_codes'] = [
                'max_count' => 1,
                'callback_url' => $this->configHelper->getCouponCallbackUrl(
                    $salesObject->getStore()->getCode()
                )
            ];

            $appliedRuleIds = array_filter(explode(',', $salesObject->getAppliedRuleIds() ?? ''));
            if (!empty($appliedRuleIds)) {
                $rule = $this->createDiscountRule($salesObject);
                if ($discountLine = $this->discountLineBuilder->build($rule)) {
                    $orderData['order']['discount_lines'][] = $this->objectConverter->buildOutputDataArray(
                        $discountLine,
                        DiscountInterface::class
                    );
                }
            }
        }

        if (!empty($customerEmail)) {
            $orderData['customer']['email'] = $customerEmail;
        }

        $shippingAddress = $salesObject->getShippingAddress()->getPostcode()
            ? $salesObject->getShippingAddress() : null;
        if (!$shippingAddress && $customer) {
            /** @var \Magento\Customer\Model\Data\Customer $customer */
            $shippingAddress = $this->_extractDefaultAddress(
                $customer->getAddresses(),
                \Magento\Customer\Api\Data\AddressInterface::DEFAULT_SHIPPING
            );
        }

        if ($shippingAddress && $shippingAddress->getPostcode() && $shippingAddress->getTelephone()) {
            $orderData['order']['shipping_address'] = $this->prepareAddress($shippingAddress);
        }

        $billingAddress = $salesObject->getBillingAddress()->getPostcode() ? $salesObject->getBillingAddress() : null;
        $billingCustomerEmail = $billingAddress && $billingAddress->getEmail()
            ? $billingAddress->getEmail() : $salesObject->getCustomerEmail();

        if ($customer && !$billingAddress) {
            $billingAddress = $this->_extractDefaultAddress(
                $customer->getAddresses(),
                \Magento\Customer\Api\Data\AddressInterface::DEFAULT_BILLING
            );
            $billingCustomerEmail = $customer->getEmail();
        }

        if ($billingAddress && $billingAddress->getPostcode() && $billingAddress->getTelephone()) {
            $orderData['order']['billing_address'] = $this->prepareAddress($billingAddress);
            $orderData['order']['billing_address']['email'] = $billingCustomerEmail ?? '';
        }

        if (!empty($this->getMetaData()) && is_array($this->getMetaData())) {
            $orderData['metadata'] = $this->getMetaData();
        }

        $dataObject = new DataObject($orderData);
        return $dataObject->toArray();
    }

    /**
     * Preparing address
     *
     * @param \Magento\Sales\Api\Data\OrderAddressInterface|\Magento\Quote\Api\Data\AddressInterface $address
     * @return array
     */
    private function prepareAddress($address)
    {
        $addressData = [
            'first_name' => $address->getFirstname(),
            'last_name' => $address->getLastname(),
            'address_line' => implode(',', $address->getStreet()),
            'postal_code' => $address->getPostcode(),
            'postal_place' => $address->getCity(),
            'country' => $address->getCountryId(),
            'phone_number' => urlencode($this->sanitizePhoneNumber($address->getTelephone() ?? '')),
        ];

        if (!empty($address->getCompany()) && !empty($address->getVatId())) {
            $addressData['business_name'] = $address->getCompany();
            $addressData['organization_number'] = $address->getVatId();
        }

        return $addressData;
    }

    /**
     * Filter amount
     *
     * @param float $amount
     * @return string
     */
    private function filterAmount($amount)
    {
        return sprintf("%f", $amount);
    }

    /**
     * Preparing invoice items
     *
     * @param Order\Invoice $invoice
     * @return array
     */
    private function prepareSalesItems(AbstractModel $invoice)
    {
        $quote = $this->quoteRepository->get($invoice->getOrder()->getQuoteId());

        $items = [];
        /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
        foreach ($invoice->getAllItems() as $item) {

            $orderItem = $item->getOrderItem();
            if ($item->isDeleted() || $orderItem->isDummy()) {
                continue;
            }

            $parentOrderItem = $item->getOrderItem()->getParentItem();
            $itemId = $parentOrderItem ? $item->getParentId() : $item->getId();

            if (!isset($items[$itemId])) {
                $quoteItemId = $parentOrderItem ? $parentOrderItem->getQuoteItemId() : $orderItem->getQuoteItemId();
                $lineId = $this->lineIdGenerator->generate(
                    $quote->getItemById($quoteItemId)
                );

                $sku = $parentOrderItem ? $parentOrderItem->getSku() : $orderItem->getSku();
                $name = $parentOrderItem ? $parentOrderItem->getName() : $orderItem->getName();

                $items[$itemId] = [
                    'id' => $sku,
                    'line_id' => $lineId,
                    'amount' => 0,
                    'description' => sprintf('%s (%s)', $name, $sku),
                    'vat_amount' => 0,
                ];
            }
            $taxAmount = (float)$item->getBaseTaxAmount();
            $discountTaxCompensation = (float)$item->getBaseDiscountTaxCompensationAmount();
            $paidInclTax = (float)$item->getBaseRowTotal()
                + $taxAmount
                + $discountTaxCompensation
                - (float)$item->getBaseDiscountAmount();
            $items[$itemId]['amount'] += $paidInclTax * 100;
            $items[$itemId]['vat_amount'] += $taxAmount * 100;
        }

        // adding shipping as a separate item
        if ($invoice->getBaseShippingAmount() > 0) {
            array_push($items, [
                'id' => $invoice->getOrder()->getShippingMethod(),
                'description' => str_replace(' - ', ', ', $invoice->getOrder()->getShippingDescription()),
                'vat_amount' => (float)$invoice->getBaseShippingTaxAmount() * 100,
                'amount' => (
                    (float)$invoice->getBaseShippingAmount()
                    + (float)$invoice->getBaseShippingTaxAmount()
                    + (float)$invoice->getBaseShippingDiscountTaxCompensationAmount()
                    - (float)$invoice->getBaseShippingDiscountAmount()
                ) * 100,
                'line_id' => $invoice->getOrder()->getShippingMethod(),
            ]);
        }

        return array_values($items);
    }

    /**
     * Preparing sales object items for sending
     *
     * @param object $salesObject
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareItems($salesObject)
    {
        $items = [];
        $isQuote = $salesObject instanceof \Magento\Quote\Model\Quote;

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $salesObject;

        if (!$isQuote) {
            $quote = $this->quoteRepository->get($salesObject->getQuoteId());
        }

        foreach ($salesObject->getAllVisibleItems() as $item) {

            $lineId = $this->lineIdGenerator->generate(
                $isQuote ? $item : $quote->getItemById($item->getQuoteItemId())
            );

            $orderItem = $this->orderItemBuilder->build([
                'item' => $item,
                'line_id' => $lineId,
            ]);
            array_push($items, $this->objectConverter->buildOutputDataArray($orderItem, ItemInterface::class));
        }

        if ($giftCardItem = $this->giftCardItemBuilder->build(['sales_object' => $salesObject])) {
            $items[] = $this->objectConverter->buildOutputDataArray(
                $giftCardItem,
                ItemInterface::class
            );
        }

        $shippingTotalsObject = $isQuote ? $salesObject->getShippingAddress() : $salesObject;

        // no need to add shipping items for express checkout as shipping options are retrieved via callback
        if ($this->isExpress()) {
            return $items;
        }

        // adding shipping as a separate item
        if (!$salesObject->getIsVirtual() && $shippingTotalsObject->getBaseShippingAmount() > 0) {
            array_push($items, [
                'id' => $shippingTotalsObject->getShippingMethod(),
                'description' => str_replace(' - ', ',', $shippingTotalsObject->getShippingDescription()),
                'quantity' => 1,
                'vat_amount' => $shippingTotalsObject->getBaseShippingTaxAmount() * 100,
                'amount' => $shippingTotalsObject->getBaseShippingInclTax() * 100,
                'line_id' => $shippingTotalsObject->getShippingMethod(),
            ]);
        }

        return $items;
    }

    /**
     * Retrieving transaction by id
     *
     * @param string $transactionId
     * @param null|string|int $scopeCode
     * @return array|bool|float|int|mixed|string|null
     * @throws ClientException
     * @throws ConverterException
     */
    public function getTransaction($transactionId, $scopeCode = null)
    {
        $this->scope = $scopeCode;
        $endpoint = $this->getCheckoutApiUri(sprintf('transactions/%s', $transactionId));
        $request = $this->initRequest($endpoint, $this->getToken())
            ->setMethod(DinteroHpClient::METHOD_GET);

        return $this->client->placeRequest($request->build());
    }

    /**
     * Capturing transaction
     *
     * @param string $transactionId
     * @param Order\Payment $payment
     * @param float $amount
     * @return bool
     * @throws ClientException
     * @throws ConverterException
     */
    public function capture($transactionId, \Magento\Sales\Model\Order\Payment $payment, $amount)
    {
        $this->scope = $payment->getOrder()->getStoreId();

        $transaction = $this->getTransaction($transactionId, $this->scope);
        if (!$this->canCaptureTransaction($transaction)) {
            throw new \Exception(__('This transaction cannot be captured'));
        }

        $requestData = [
            'id' => $transactionId,
            'amount' => $amount * 100,
            'items' => $payment->getSalesDocument() ?
                $this->prepareSalesItems($payment->getSalesDocument()) : $this->prepareItems($payment->getOrder())
        ];

        $endpoint = $this->getCheckoutApiUri(sprintf('transactions/%s/capture', $transactionId));
        $request = $this->initRequest($endpoint, $this->getToken())
            ->setBody($requestData);

        return $this->client->placeRequest($request->build());
    }

    /**
     * Refunding
     *
     * @param Order\Payment $payment
     * @param float $amount
     * @return array|bool|float|int|mixed|string|null
     * @throws ConverterException
     */
    public function refund(\Magento\Sales\Model\Order\Payment $payment, $amount)
    {
        $this->scope = $payment->getSalesDocument()->getStoreId();
        $transactionId = str_replace(
            '-' . TransactionInterface::TYPE_CAPTURE,
            '',
            $payment->getParentTransactionId()
        );

        $requestData = [
            'id' => $transactionId,
            'amount' => $amount * 100,
            'items' => $this->prepareSalesItems($payment->getSalesDocument())
        ];

        $endpoint = $this->getCheckoutApiUri(sprintf('transactions/%s/refund', $transactionId));

        $request = $this->initRequest($endpoint, $this->getToken())
            ->setBody($requestData);

        return $this->client->placeRequest($request->build());
    }

    /**
     * Voiding transaction
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return array|bool|float|int|mixed|string|null
     * @throws ClientException
     * @throws ConverterException
     */
    public function void($payment)
    {
        $transactionId = $payment->getParentTransactionId() ?: $payment->getLastTransId();
        $this->scope = $payment->getOrder()->getStoreId();
        $endpoint = $this->getCheckoutApiUri(sprintf('transactions/%s/void', $transactionId));
        $request = $this->initRequest($endpoint, $this->getToken());
        return $this->client->placeRequest($request->build());
    }

    /**
     * Retrieving session
     *
     * @param string $sessionId
     * @param null|int|string $scopeCode
     * @return array|bool|float|int|mixed|string|null
     * @throws ClientException
     * @throws ConverterException
     */
    public function getSessionInfo($sessionId, $scopeCode = null)
    {
        $this->scope = $scopeCode;
        $endpoint = $this->getCheckoutApiUri(sprintf('sessions/%s', $sessionId));
        $request = $this->initRequest($endpoint, $this->getToken())
            ->setMethod(DinteroHpClient::METHOD_GET);
        return $this->client->placeRequest($request->build());
    }

    /**
     * Cancel Dintero session
     *
     * @param string $sessionId
     * @param int|null $scopeCode
     * @return array|bool|float|int|mixed|string|null
     * @throws ClientException
     * @throws ConverterException
     */
    public function cancelSession($sessionId, $scopeCode = null)
    {
        $this->scope = $scopeCode;
        $endpoint = $this->getCheckoutApiUri(sprintf('sessions/%s/cancel', $sessionId));
        $request = $this->initRequest($endpoint, $this->getToken())
            ->setMethod(DinteroHpClient::METHOD_POST)
            ->setBody(null);
        return $this->client->placeRequest($request->build());
    }

    /**
     * Checking whether transaction can be captured or not
     *
     * @param array $transaction
     * @return bool
     */
    private function canCaptureTransaction($transaction)
    {
        return isset($transaction['status']) &&
            in_array($transaction['status'], [self::STATUS_AUTHORIZED, self::STATUS_PARTIALLY_CAPTURED]);
    }

    /**
     * Retrieve agreements
     *
     * @return \Magento\CheckoutAgreements\Api\Data\AgreementInterface[]
     */
    private function getAgreements()
    {
        return $this->objectManager->get(\Magento\CheckoutAgreements\Block\Agreements::class)->getAgreements();
    }
}
