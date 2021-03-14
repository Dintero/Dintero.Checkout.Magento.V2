<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Helper\Config;
use Dintero\Checkout\Model\Payment\TransactionStatusResolver;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Psr\Log\LoggerInterface;
use Dintero\Checkout\Model\Api\Client;
use Magento\Framework\Registry;

/**
 * Class EmbeddedCallback
 *
 * @package Dintero\Checkout\Model
 */
class EmbeddedCallback implements \Dintero\Checkout\Api\EmbeddedCallbackInterface
{
    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var RequestInterface $request
     */
    protected $request;

    /**
     * @var SerializerInterface $serializer
     */
    protected $serializer;

    /**
     * @var DataObjectFactory $dataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var CartManagementInterface $cartManagement
     */
    protected $cartManagement;

    /**
     * @var Builder $transactionBuilder
     */
    protected $transactionBuilder;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var Quote $quoteResource
     */
    protected $quoteResource;

    /**
     * @var CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;

    /**
     * @var Client $client
     */
    protected $client;

    /**
     * @var TransactionStatusResolver $transactionStatusResolver
     */
    protected $transactionStatusResolver;

    /**
     * @var Config $configHelper
     */
    protected $configHelper;

    /**
     * @var ObjectManagerInterface $objectManager
     */
    protected $objectManager;

    /**
     * @var InvoiceManagementInterface $invoiceManagement
     */
    protected $invoiceManagement;

    /**
     * @var Registry $registry
     */
    protected $registry;

    /**
     * @var InvoiceRepositoryInterface $invoiceRepository
     */
    protected $invoiceRepository;

    /**
     * EmbeddedCallback constructor.
     *
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     * @param QuoteFactory $quoteFactory
     * @param Quote $quoteResource
     * @param DataObjectFactory $dataObjectFactory
     * @param CartManagementInterface $cartManagement
     * @param Builder $transactionBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param Client $client
     * @param TransactionStatusResolver $transactionStatusResolver
     * @param Config $configHelper
     * @param ObjectManagerInterface $objectManager
     * @param InvoiceManagementInterface $invoiceManagement
     * @param Registry $registry
     */
    public function __construct(
        LoggerInterface $logger,
        RequestInterface $request,
        SerializerInterface $serializer,
        QuoteFactory $quoteFactory,
        Quote $quoteResource,
        DataObjectFactory $dataObjectFactory,
        CartManagementInterface $cartManagement,
        Builder $transactionBuilder,
        CustomerRepositoryInterface $customerRepository,
        Client $client,
        TransactionStatusResolver $transactionStatusResolver,
        Config $configHelper,
        ObjectManagerInterface $objectManager,
        InvoiceManagementInterface $invoiceManagement,
        Registry $registry,
        InvoiceRepositoryInterface $invoiceRepository
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->serializer = $serializer;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->cartManagement = $cartManagement;
        $this->transactionBuilder = $transactionBuilder;
        $this->customerRepository = $customerRepository;
        $this->quoteResource =  $quoteResource;
        $this->quoteFactory =  $quoteFactory;
        $this->client = $client;
        $this->transactionStatusResolver = $transactionStatusResolver;
        $this->configHelper = $configHelper;
        $this->objectManager = $objectManager;
        $this->invoiceManagement = $invoiceManagement;
        $this->registry = $registry;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * @return mixed|void
     */
    public function execute()
    {
        $request = $this->dataObjectFactory->create([
            'data' => $this->serializer->unserialize($this->request->getContent())
        ]);

        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->quoteFactory->create();
            $this->quoteResource->load($quote, $request->getMerchantReference(), 'reserved_order_id');
            $sessionId = $quote->getPayment()->getAdditionalInformation('id');
            if (!$sessionId || $sessionId != $request->getSessionId()) {
                throw new \Exception(__('Quote is not valid'));
            }

            $quote->setCustomerEmail($quote->getBillingAddress()->getEmail() ?? $quote->getShippingAddress()->getEmail());
            $quote->setCheckoutMethod($this->resolveCheckoutMethod($quote));

            $quote->getPayment()->setMethod(Dintero::METHOD_CODE);

            $quote->collectTotals();
            $quote->save();

            $dinteroTransaction = $this->client->getTransaction($request->getId());

            if (isset($dinteroTransaction['error'])) {
                throw new \Exception(__('Transaction is invalid'));
            }

            /** @var Order $order */
            $order = $this->cartManagement->submit($quote);
            $paymentObject = $order->getPayment();
            $paymentObject->setCcTransId($request->getId())
                ->setLastTransId($request->getId());

            $transaction = $this->transactionBuilder->setPayment($paymentObject)
                ->setOrder($order)
                ->setTransactionId($request->getId())
                ->build($this->transactionStatusResolver->resolve($dinteroTransaction['status']));

            $transaction->setIsClosed($transaction->getTxnType() == TransactionInterface::TYPE_CAPTURE)->save();
            if ($order->canInvoice()) {

                /** @var Invoice $invoice */
                $invoice = $order->prepareInvoice()
                    ->setTransactionId($request->getId())
                    ->register()
                    ->save();

                if ($invoice->canCapture() && $this->configHelper->isAutocaptureEnabled()) {
                    $this->triggerCapture($invoice);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function resolveCheckoutMethod(\Magento\Quote\Model\Quote $quote)
    {
        try {
            $customer = $this->customerRepository->get($quote->getCustomerEmail());
            $quote->setCustomer($customer)
                ->setCustomerId($customer->getId())
                ->setCustomerIsGuest(false);
            return null;
        } catch (NoSuchEntityException $e) {
            $quote->setCustomerIsGuest(true);
            return CartManagementInterface::METHOD_GUEST;
        }
    }

    /**
     * Triggering capture
     *
     * @param Invoice $invoice
     * @throws \Exception
     */
    private function triggerCapture(Invoice $invoice)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $this->invoiceRepository->get($invoice->getEntityId());
        $this->registry->register('current_invoice', $invoice);
        $this->invoiceManagement->setCapture($invoice->getEntityId());
        $invoice->getOrder()->setIsInProcess(true);
        $this->objectManager->create(Transaction::class)
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();
    }
}
