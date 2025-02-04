<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Helper\Config;
use Dintero\Checkout\Model\Api\Client;
use Dintero\Checkout\Model\Gateway\ResponseFactory;
use Dintero\Checkout\Model\Payment\Response;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Api\TransactionRepositoryInterface;

/**
 * Class Dintero
 *
 * @package Dintero\Payment\Model
 */
class Dintero extends AbstractMethod
{
    /*
     * Method code
     */
    const METHOD_CODE = 'dintero';

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCaptureOnce = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canReviewPayment = false;

    /**
     * This may happen when amount is captured, but not settled
     *
     * @var bool
     */
    protected $_canCancelInvoice = true;

    /**
     * Order factory
     *
     * @var OrderFactory $orderFactory
     */
    protected $orderFactory;

    /**
     * Client
     *
     * @var Client $client
     */
    protected $client;

    /**
     * Response
     *
     * @var \Dintero\Checkout\Model\Gateway\Response $response
     */
    protected $response;

    /**
     * @var LoggerInterface $psrLogger
     */
    protected $psrLogger;

    /**
     * Adapter
     *
     * @var Adapter $adapter
     */
    protected $adapter;

    /**
     * Payment session
     *
     * @var Response $paymentSession
     */
    protected $paymentSession;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     */
    protected $orderSender;

    /**
     * @var OrderResource $orderResource
     */
    protected $orderResource;

    /**
     * @var string
     */
    protected $_infoBlockType = \Dintero\Checkout\Block\Info::class;

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * @var TransactionRepositoryInterface $transactionManager
     */
    protected $transactionRepository;

    /**
     * @var array $failedTransactionStatuses
     */
    protected $failedTransactionStatuses = [
        Client::STATUS_FAILED,
        Client::STATUS_DECLINED,
        Client::STATUS_UNKNOWN
    ];

    /**
     * Define class dependencies
     *
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param OrderFactory $orderFactory
     * @param Client $client
     * @param Adapter $adapter
     * @param ResponseFactory $responseFactory
     * @param OrderSender $orderSender
     * @param OrderResource $orderResource
     * @param Config $config
     * @param TransactionRepositoryInterface $transactionRepository
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param DirectoryHelper|null $directory
     */
    public function __construct(
        Context                    $context,
        Registry                   $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory      $customAttributeFactory,
        Data                       $paymentData,
        ScopeConfigInterface       $scopeConfig,
        Logger                     $logger,
        OrderFactory               $orderFactory,
        Client                     $client,
        Adapter                    $adapter,
        ResponseFactory            $responseFactory,
        OrderSender                $orderSender,
        OrderResource              $orderResource,
        Config                     $config,
        TransactionRepositoryInterface $transactionRepository,
        AbstractResource           $resource = null,
        AbstractDb                 $resourceCollection = null,
        array                      $data = [],
        DirectoryHelper            $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
        $this->_code = self::METHOD_CODE;
        $this->orderFactory = $orderFactory;
        $this->client = $client;
        $this->response = $responseFactory->create();
        $this->paymentSession = $responseFactory->create();
        $this->adapter = $adapter;
        $this->orderSender = $orderSender;
        $this->orderResource = $orderResource;
        $this->config = $config;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Return response.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Retrieving payment session
     *
     * @return Response
     */
    public function getPaymentSession()
    {
        return $this->paymentSession;
    }

    /**
     * Set initialization requirement state
     *
     * @param bool $isInitializeNeeded
     * @return void
     */
    public function setIsInitializeNeeded($isInitializeNeeded = true)
    {
        $this->_isInitializeNeeded = (bool)$isInitializeNeeded;
    }

    /**
     * Process
     *
     * @param string $merchantOrderId
     * @param string $transactionId
     * @param string $sessionId
     * @throws LocalizedException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function process($merchantOrderId, $transactionId, $sessionId = null)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderFactory->create()->loadByIncrementId($merchantOrderId);
        $payment = $order->getPayment();
        if (!$payment || $payment->getMethod() != $this->getCode()) {
            throw new LocalizedException(
                __("This payment didn't work out because we can\'t find this order.")
            );
        }

        $this->getResponse()->setData($this->client->getTransaction($transactionId, $order->getStoreId()));

        // if payment has transaction that means that it was already processed
        if ($payment->getLastTransId()) {
            $this->processTransaction($payment);
            return;
        }

        $this->getPaymentSession()->setData(
            $this->client->getSessionInfo(
                $sessionId ?? $this->getResponse()->getSessionId(),
                $order->getStoreId()
            )
        );

        $this->logger->debug([
            'Processing order #: ' . $merchantOrderId,
            'Order real id: ' . $order->getId(),
        ]);

        if ($order->getId()) {
            $this->processOrder($order);
        }
    }

    /**
     * Processing order
     *
     * @param Order $order
     * @throws \Exception
     */
    public function processOrder($order)
    {
        $isFailed = false;
        try {
            $this->checkPaymentSession();
            $this->checkTransaction($order);
        } catch (\Exception $e) {
            //decline the order (in case of wrong response code) but don't return money to customer.
            $message = $e->getMessage();
            $this->declineOrder($order, $message, false);
            $isFailed = true;
        }

        $payment = $order->getPayment();
        $this->fillPaymentByResponse($payment);
        $payment->getMethodInstance()->setIsInitializeNeeded(false);
        $payment->getMethodInstance()->setResponseData($this->getResponse()->getData());

        // allow placing only if transaction is not pending (on hold)
        if (!$payment->getIsTransactionPending()
            && !$order->isCanceled()
            && $this->getResponse()->getStatus() !== Client::STATUS_FAILED
        ) {
            $payment->place();
        }

        if ($this->getResponse()->getStatus() === Client::STATUS_ON_HOLD) {
            $order->setStatus('dintero_pending_approval');
        }

        $this->addStatusComment($payment);
        $this->orderResource->save($order);

        if ($this->getResponse()->getStatus() === Client::STATUS_AUTHORIZED && $this->config->canCreateInvoice()) {
            $order->prepareInvoice()
                ->setTransactionId($this->getResponse()->getId())
                ->register()
                ->save();
        }

        $this->sendOrderEmail($order, !$isFailed);
    }

    /**
     * Processing authorization transaction
     *
     * @param string $transactionId
     * @param $payment
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    private function processAuthTransaction($transactionId, $payment)
    {
        try {
            /** @var Transaction $transaction */
            $transaction = $this->transactionRepository->getByTransactionId(
                $transactionId,
                $payment->getId(),
                $payment->getOrder()->getId()
            );

            if ($transaction->getTxnType() != Transaction::TYPE_ORDER) {
                return;
            }

            $transaction->setTxnType(Transaction::TYPE_AUTH);
            $this->transactionRepository->save($transaction);

        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return void
     */
    private function processTransaction($payment)
    {
        // Decline order for failed transaction

        if (
            in_array($this->response->getData('status'), $this->failedTransactionStatuses)
            && $payment->getOrder()->canCancel()
        ) {
            $this->declineOrder(
                $payment->getOrder(),
                __(
                    'Transaction %1 failed with status: %2',
                    $this->response->getData('status'),
                    $this->response->getData('id')
                ),
                false
            );
        }

        $transaction = $payment->getAuthorizationTransaction();
        if ($transaction && $transaction->getTxnId() !== $payment->getLastTransId()) {
            return;
        }

        if ($this->getResponse()->getData('status') === Client::STATUS_CAPTURED) {
            $payment->registerCaptureNotification($this->getResponse()->getData('amount') / 100, true);
            $payment->getOrder()->save();
        }

        if ($this->response->getData('status') === Client::STATUS_AUTHORIZED) {
            $this->processAuthTransaction($this->response->getData('id'), $payment);
        }
    }

    /**
     * Send order confirmation email
     *
     * @param \Magento\Sales\Model\Order $order
     * @throws \Exception
     */
    public function sendOrderEmail($order, $canSend = false)
    {
        // reloading order to avoid duplicate sending
        $this->orderResource->load($order, $order->getId());

        if (!$canSend || $order->getSendEmail()) {
            return;
        }

        try {
            $this->orderSender->send($order);
            $order->addStatusHistoryComment(__("Notified customer about order #%1", $order->getIncrementId()))
                ->setIsCustomerNotified(1)
                ->save();
        } catch (\Exception $e) {
            $order->addStatusHistoryComment(__("Could not send order confirmation for order #%1", $order->getIncrementId()))
                ->setIsCustomerNotified(0)
                ->save();
        }
    }

    /**
     * Validating order
     *
     * @param Order $order
     * @throws \Exception
     */
    protected function checkTransaction($order)
    {
        if (!$order->canInvoice()) {
            throw new \Exception(__('Cannot invoice the transaction'));
        }

        if (!$this->getResponse()->getId() ||
            $order->getIncrementId() !== $this->getResponse()->getMerchantReference()) {
            throw new \Exception(__('Invalid transaction or merchant reference'));
        }

        if (in_array($this->getResponse()->getStatus(), $this->failedTransactionStatuses)) {
            throw new \Exception(__(
                'Transaction %1 failed with status %2 ',
                $this->getResponse()->getId(),
                $this->getResponse()->getStatus()
            ));
        }
    }

    /**
     * Validating payment session
     *
     * @throws \Exception
     */
    protected function checkPaymentSession()
    {
        if ($this->getResponse()->getId() !== $this->getPaymentSession()->getTransactionId()) {
            throw new \Exception(__('Payment session validation failed!'));
        }
    }

    /**
     * Register order cancellation. Return money to customer if needed.
     *
     * @param Order $order
     * @param string $message
     * @param bool $voidPayment
     * @return void
     */
    public function declineOrder(Order $order, $message = '', $voidPayment = true)
    {
        try {
            $response = $this->getResponse();
            if ($voidPayment && $response->getId()) {
                $order->getPayment()
                    ->setTransactionId(null)
                    ->setParentTransactionId($response->getId())
                    ->void($response);
            }
            $order->registerCancellation($message)->save();
            $this->_eventManager->dispatch('order_cancel_after', ['order' => $order ]);
        } catch (\Exception $e) {
            $this->getPsrLogger()->critical($e);
            throw $e;
        }
    }

    /**
     * Get psr logger.
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function getPsrLogger()
    {
        if (null === $this->psrLogger) {
            $this->psrLogger = ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class);
        }
        return $this->psrLogger;
    }

    /**
     * Fill payment with credit card data from response from Dintero.
     *
     * @param \Magento\Framework\DataObject $payment
     * @return void
     */
    protected function fillPaymentByResponse(\Magento\Framework\DataObject $payment)
    {
        $response = $this->getResponse();
        $payment->setTransactionId($response->getId())
            ->setParentTransactionId(null)
            ->setIsTransactionClosed(0)
            ->setIsTransactionPending($response->getStatus() === Client::STATUS_ON_HOLD);

        $payment->setAdditionalInformation('payment_product', $response->getData('payment_product_type'));
        $payment->setDinteroPaymentProduct($response->getData('payment_product_type'));
    }

    /**
     * Add status comment to history
     *
     * @param Payment $payment
     * @return $this
     */
    protected function addStatusComment(Payment $payment)
    {
        $transactionId = $this->getResponse()->getId();
        if ($payment->getIsTransactionPending()) {
            $message = 'Amount of %1 is pending approval on the gateway.<br/>'
                    . 'Transaction "%2" status is "%3".';

            $message = __(
                    $message,
                    $payment->getOrder()->getBaseCurrency()->formatTxt($this->getResponse()->getAmount()/100),
                    $transactionId,
                    $this->getResponse()->getStatus()
                );

            $payment->getOrder()->addStatusHistoryComment($message);
        }

        return $this;
    }

    /**
     * Capture amount
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return AbstractMethod|Adapter
     */
    public function capture(InfoInterface $payment, $amount)
    {
        return $this->adapter->capture($payment, $amount);
    }

    /**
     * Authorize
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return AbstractMethod|Adapter
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        return $this->adapter->authorize($payment, $amount);
    }

    /**
     * Cancel
     *
     * @param InfoInterface $payment
     * @return AbstractMethod|Adapter
     */
    public function cancel(InfoInterface $payment)
    {
        return $this->adapter->void($payment);
    }

    /**
     * Refund
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return AbstractMethod|Adapter
     */
    public function refund(InfoInterface $payment, $amount)
    {
        return $this->adapter->refund($payment, $amount);
    }

    /**
     * Voiding payment
     *
     * @param InfoInterface $payment
     * @return AbstractMethod|mixed
     */
    public function void(InfoInterface $payment)
    {
        return $this->adapter->void($payment);
    }

    /**
     * Fetching transaction info
     *
     * @param InfoInterface $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        return $this->adapter->fetchTransactionInfo($payment, $transactionId);
    }
}
