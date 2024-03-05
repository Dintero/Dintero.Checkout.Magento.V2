<?php

namespace Dintero\Checkout\Model\Api;

use Dintero\Checkout\Helper\Config as ConfigHelper;
use Dintero\Checkout\Model\Dintero;
use Dintero\Checkout\Model\Gateway\Http\Client as DinteroHpClient;
use Dintero\Checkout\Model\Payment\Token;
use Dintero\Checkout\Model\Payment\TokenFactory;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\TransferBuilderFactory;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * API Client for Dintero payment method
 *
 * @package Dintero\Checkout\Model\Gateway\Http
 */
class Client
{
    /*
     * Dintero api endpoint
     */
    const API_BASE_URL = 'https://api.dintero.com/v1';

    /*
     * Checkout api endpoint
     */
    const CHECKOUT_API_BASE_URL = 'https://checkout.dintero.com/v1';

    /*
     * Status captured
     */
    const STATUS_CAPTURED = 'CAPTURED';

    /*
     * Status authorized
     */
    const STATUS_AUTHORIZED = 'AUTHORIZED';

    /*
     * Status on hold
     */
    const STATUS_ON_HOLD = 'ON_HOLD';

    /*
     * Status failed
     */
    const STATUS_FAILED = 'FAILED';

    /*
     * Status partially captured
     */
    const STATUS_PARTIALLY_CAPTURED = 'PARTIALLY_CAPTURED';

    /*
     * Standard
     */
    const TYPE_STANDARD = 'standard';

    /*
     * Express
     */
    const TYPE_EXPRESS = 'express';

    /*
     * Embedded
     */
    const TYPE_EMBEDDED = 'embedded';

    /**
     * HTTP Client
     *
     * @var DinteroHpClient $client
     */
    private $client;

    /**
     * Config helper
     *
     * @var ConfigHelper $configHelper
     */
    private $configHelper;

    /**
     * Transfer builder factory
     *
     * @var TransferBuilderFactory $transferBuilderFactory
     */
    private $transferBuilderFactory;

    /**
     * Token factory
     *
     * @var TokenFactory $tokenFactory
     */
    private $tokenFactory;

    /**
     * Logger
     *
     * @var LoggerInterface $logger
     */
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
     * @var ObjectManagerInterface $objectManager
     */
    protected $objectManager;

    /**
     * Client constructor.
     *
     * @param DinteroHpClient $client
     * @param ConfigHelper $configHelper
     * @param TransferBuilderFactory $transferBuilderFactory
     * @param TokenFactory $tokenFactory
     * @param LoggerInterface $logger
     * @param Json $converter
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        DinteroHpClient $client,
        ConfigHelper $configHelper,
        TransferBuilderFactory $transferBuilderFactory,
        TokenFactory $tokenFactory,
        LoggerInterface $logger,
        Json $converter,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        ObjectManagerInterface $objectManager
    ) {
        $this->client = $client;
        $this->configHelper = $configHelper;
        $this->transferBuilderFactory = $transferBuilderFactory;
        $this->tokenFactory = $tokenFactory;
        $this->logger = $logger;
        $this->converter = $converter;
        $this->quoteResource = $quoteResource;
        $this->type = self::TYPE_STANDARD;
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type ?? self::TYPE_STANDARD;
    }

    /**
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
            'Dintero-System-Plugin-Version' => '1.7.15',
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
     * @return bool
     */
    private function isExpress()
    {
        return $this->getType() === self::TYPE_EXPRESS && $this->configHelper->isExpress();
    }

    /**
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
        $request = $this->initRequest(
            $this->getCheckoutApiUri('sessions-profile'),
            $this->getToken()
        )->setBody($this->prepareData($order, null));

        return $this->client->placeRequest($request->build());
    }

    /**
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
        $requestData = [
            'remove_lock' => true,
            'order' => [
                'amount' => $quote->getBaseGrandTotal() * 100,
                'currency' => $quote->getBaseCurrencyCode(),
                'merchant_reference' =>  $quote->getReservedOrderId(),
                'items' => $this->prepareItems($quote),
            ]
        ];

        $request = $this->initRequest(
            $this->getCheckoutApiUri(sprintf('sessions/%s', $sessionId)),
            $this->getToken()
        )->setBody($requestData)
            ->setMethod(DinteroHpClient::METHOD_PUT);

        return $this->client->placeRequest($request->build());
    }

    /**
     * @param Quote $quote
     * @return array|bool|float|int|mixed|string|null
     * @throws ClientException
     * @throws ConverterException
     */
    public function initSessionFromQuote(Quote $quote)
    {
        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId();
            $this->quoteResource->save($quote);
        }

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
     * @throws \Exception
     * @return array
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
     * Preparing data for submission
     *
     * @param Order|\Magento\Quote\Model\Quote $salesObject
     * @param AbstractModel|null $salesDocument
     * @return array
     */
    private function prepareData($salesObject, $salesDocument = null)
    {
        ;
        $customerEmail = $salesObject->getCustomerIsGuest() ?
            $salesObject->getBillingAddress()->getEmail() :
            $salesObject->getCustomerEmail();
        $baseOrderTotal = $salesDocument ? $salesDocument->getBaseGrandTotal() : $salesObject->getBaseGrandTotal();
        $orderData = [
            'profile_id' => $this->configHelper->getProfileId(),
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
                'merchant_reference' =>  $salesObject->getReservedOrderId() ?? $salesObject->getIncrementId(),
                'items' => $this->prepareItems($salesObject),
            ],
        ];

        if ($salesObject->getBillingAddress()->getPostcode()) {
            $orderData['customer'] = [
                'phone_number' => $salesObject->getBillingAddress()->getTelephone()
            ];
            $orderData['order']['billing_address'] = $this->prepareAddress($salesObject->getBillingAddress());
        }

        if ($this->isExpress()) {
            $orderData['express']['customer_types'] = ['b2c', 'b2b'];
            $orderData['express']['shipping_address_callback_url'] = $this->configHelper->getShippingCallbackUrl(
                $salesObject->getStore()->getCode()
            );
            $orderData['express']['shipping_options'] = [];
        }

        if (!empty($customerEmail)) {
            $orderData['customer']['email'] = $customerEmail;
        }

        if ($salesObject->getShippingAddress() && $salesObject->getShippingAddress()->getPostcode()) {
            $orderData['order']['shipping_address'] = $this->prepareAddress($salesObject->getShippingAddress());
        }

        if (!empty($this->getMetaData()) && is_array($this->getMetaData())) {
            $orderData['metadata'] = $this->getMetaData();
        }

        $dataObject = new DataObject($orderData);
        return $dataObject->toArray();
    }

    /**
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
            'phone_number' => urlencode($this->sanitizePhoneNumber($address->getTelephone())),
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
        $items = [];
        /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
        foreach ($invoice->getAllItems() as $item) {
            if ($item->isDeleted() || $item->getOrderItem()->getParentItemId()) {
                continue;
            }

            array_push($items, [
                'id' => $item->getSku(),
                'line_id' => $item->getSku(),
                'amount' => ($item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount()) * 100,
            ]);
        }

        // adding shipping as a separate item
        if ($invoice->getBaseShippingAmount() > 0) {
            array_push($items, [
                'id' => $invoice->getOrder()->getShippingMethod(),
                'description' => str_replace(' - ', ', ', $invoice->getOrder()->getShippingDescription()),
                'vat_amount' => $invoice->getBaseShippingTaxAmount() * 100,
                'amount' => $invoice->getBaseShippingInclTax() * 100,
                'line_id' => $invoice->getOrder()->getShippingMethod(),
            ]);
        }

        return $items;
    }

    /**
     * Preparing order items for sending
     *
     * @param Order|\Magento\Quote\Model\Quote $order
     * @return array
     */
    private function prepareItems($salesObject)
    {
        $items = [];
        $isQuote = $salesObject instanceof \Magento\Quote\Model\Quote;
        foreach ($salesObject->getAllVisibleItems() as $item) {
            $itemAmount = $item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount();
            array_push($items, [
                'id' => $item->getSku(),
                'description' => sprintf('%s (%s)', $item->getName(), $item->getSku()),
                'quantity' => ($isQuote ? $item->getQty() : $item->getQtyOrdered()) * 1,
                'amount' =>  $this->filterAmount($itemAmount) * 100,
                'line_id' => $item->getSku(),
                'vat_amount' => $item->getBaseTaxAmount() * 100, // NOK cannot be floating
                'vat' => $item->getTaxPercent() * 1,
            ]);
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
     * @param $amount
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
     * @param string $transactionId
     * @param Order\Payment $payment
     * @param $amount
     * @return array|bool|float|int|mixed|string|null
     * @throws ClientException
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
            // @todo fix incorrect items amount
            // 'items' => $this->prepareSalesItems($payment->getSalesDocument())
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
        $request = $this->initRequest($endpoint, $this->getToken())->setBody(null);
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
     * @param $sessionId
     * @param null $scopeCode
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
}
