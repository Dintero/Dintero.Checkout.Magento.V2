<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Helper\Config;
use Dintero\Checkout\Model\Api\Client;
use Dintero\Checkout\Model\Payment\TransactionStatusResolver;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\Registry;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Magento\Sales\Api\OrderRepositoryInterface;

class CreateOrder
{
    /**
     * @var Client $apiClient
     */
    private $apiClient;

    /**
     * @var CartManagementInterface $cartManagement
     */
    private $cartManagement;

    /**
     * @var Quote $quoteResource
     */
    private $quoteResource;

    /**
     * @var QuoteFactory $quoteFactory
     */
    private $quoteFactory;

    /**
     * @var AddressMapperFactory $addressMapperFactory
     */
    private $addressMapperFactory;

    /**
     * @var Registry $registry
     */
    private $registry;

    /**
     * @var InvoiceRepositoryInterface $invoiceRepository
     */
    private $invoiceRepository;

    /**
     * @var CustomerRepositoryInterface $customerRepository
     */
    private $customerRepository;

    /**
     * @var Config $configHelper
     */
    private $configHelper;

    /**
     * @var Builder $transactionBuilder
     */
    private $transactionBuilder;

    /**
     * @var TransactionStatusResolver $transactionStatusResolver
     */
    private $transactionStatusResolver;

    /**
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    /**
     * @var InvoiceManagementInterface $invoiceManagement
     */
    private $invoiceManagement;

    /**
     * @var OrderRepositoryInterface $orderRepository
     */
    protected $orderRepository;

    /**
     * @var DinteroFactory $paymentMethodFactory
     */
    protected $paymentMethodFactory;

    /**
     * @var TransactionFactory $dinteroTransactionFactory
     */
    protected $dinteroTransactionFactory;

    /**
     * Define class dependencies
     *
     * @param Client $apiClient
     * @param CartManagementInterface $cartManagement
     * @param Quote $quoteResource
     * @param QuoteFactory $quoteFactory
     * @param AddressMapperFactory $addressMapperFactory
     * @param Registry $registry
     * @param CustomerRepositoryInterface $customerRepository
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param Config $config
     * @param Builder $transactionBuilder
     * @param TransactionStatusResolver $transactionStatusResolver
     * @param InvoiceManagementInterface $invoiceManagement
     * @param ObjectManagerInterface $objectManager
     * @param OrderRepositoryInterface $orderRepository
     * @param DinteroFactory $paymentMethodFactory
     * @param TransactionFactory $dinteroTransactionFactory
     */
    public function __construct(
        Client $apiClient,
        CartManagementInterface $cartManagement,
        Quote $quoteResource,
        QuoteFactory $quoteFactory,
        AddressMapperFactory $addressMapperFactory,
        Registry $registry,
        CustomerRepositoryInterface $customerRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        Config $config,
        Builder $transactionBuilder,
        TransactionStatusResolver $transactionStatusResolver,
        InvoiceManagementInterface $invoiceManagement,
        ObjectManagerInterface $objectManager,
        OrderRepositoryInterface $orderRepository,
        DinteroFactory $paymentMethodFactory,
        TransactionFactory $dinteroTransactionFactory
    ) {
        $this->apiClient = $apiClient;
        $this->cartManagement = $cartManagement;
        $this->quoteFactory = $quoteFactory;
        $this->quoteResource = $quoteResource;
        $this->addressMapperFactory = $addressMapperFactory;
        $this->configHelper = $config;
        $this->registry = $registry;
        $this->customerRepository = $customerRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->transactionStatusResolver = $transactionStatusResolver;
        $this->objectManager = $objectManager;
        $this->invoiceManagement = $invoiceManagement;
        $this->orderRepository = $orderRepository;
        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->dinteroTransactionFactory = $dinteroTransactionFactory;
    }

    /**
     * Check and process invoice if allowed
     *
     * @param \Magento\Sales\Model\Order $order
     * @param $transaction
     * @return void
     * @throws \Exception
     */
    private function processInvoice($order, $transaction)
    {
        // register offline capture if transaction is closed already
        if ($transaction->getIsClosed()) {
            $order->getPayment()->registerCaptureNotification($order->getGrandTotal());
            $order->save();
            return;
        }

        /** @var Invoice $invoice */
        $invoice = $order->prepareInvoice()
            ->setTransactionId($transaction->getId())
            ->register()
            ->save();

        if ($invoice->canCapture() && $this->configHelper->isAutocaptureEnabled() && !$transaction->getIsClosed()) {
            $this->triggerCapture($invoice);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param string $transactionId
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function createFromTransaction($quote, $transactionId)
    {
        $transactionData = $this->apiClient->getTransaction($transactionId, $quote->getStoreId());
        if (isset($transactionData['error'])) {
            throw new \Exception(__('Transaction is invalid'));
        }

        if ($quote->getReservedOrderId() && $quote->getReservedOrderId() != $transactionData['merchant_reference']) {
            $quote = $this->quoteFactory->create();
            $this->quoteResource->load($quote, $transactionData['merchant_reference'], 'reserved_order_id');
        }

        if (!$quote->getId()) {
            throw new \Exception(__('Quote not found'));
        }

        /** @var \Dintero\Checkout\Model\Transaction $dinteroTransaction */
        $dinteroTransaction = $this->dinteroTransactionFactory->create()->setData($transactionData);

        if ($dinteroTransaction->isFailed()) {
            throw new \Exception(__(
                'Cannot create order from transactions %1. Transaction status: %2',
                $dinteroTransaction->getId(),
                $dinteroTransaction->getStatus()
            ));
        }


        // populating billing address with data from dintero
        $this->addressMapperFactory
            ->create(['address' => $quote->getBillingAddress(), 'dataObject' => $dinteroTransaction])
            ->map();

        if (!$quote->isVirtual() && $dinteroTransaction->getShippingAddress()) {
            // populating shipping address data from dintero
            $this->addressMapperFactory
                ->create(['address' => $quote->getShippingAddress(), 'dataObject' => $dinteroTransaction])
                ->map();
        }

        if ($quote->getShippingAddress()->getId() && !$quote->getShippingAddress()->getShippingMethod()) {
            $quote->getShippingAddress()->setShippingMethod($dinteroTransaction->getData('shipping_option/id'));
        }

        if (!$quote->getCustomerEmail()) {
            $quote->setCustomerEmail(
                !$quote->isVirtual() ? $quote->getBillingAddress()->getEmail() : $quote->getShippingAddress()->getEmail()
            );
        }

        $quote->getPayment()->setMethod(Dintero::METHOD_CODE);
        $this->updateCustomerInfo($quote);
        $quote->collectTotals();
        $this->quoteResource->save($quote);

        /** @var Order $order */
        $order = $this->cartManagement->submit($quote);

        $paymentObject = $order->getPayment();
        $paymentObject->setCcTransId($dinteroTransaction->getId())
            ->setLastTransId($dinteroTransaction->getId())
            ->setAdditionalInformation('payment_product', $dinteroTransaction->getData('payment_product_type'))
            ->setDinteroPaymentProduct($dinteroTransaction->getData('payment_product_type'));
        $paymentObject->save();
        $transaction = $this->transactionBuilder->setPayment($paymentObject)
            ->setOrder($order)
            ->setTransactionId($dinteroTransaction->getId())
            ->build($this->transactionStatusResolver->resolve($dinteroTransaction->getStatus()));

        $transaction->setIsClosed($dinteroTransaction->getStatus() == Client::STATUS_CAPTURED)->save();

        if ($this->canInvoice($order, $dinteroTransaction)) {
            $this->processInvoice($order, $transaction);
        }

        /** @var \Dintero\Checkout\Model\Dintero $paymentMethodInstance */
        $paymentMethodInstance = $this->paymentMethodFactory->create();

        if ($dinteroTransaction->getStatus() == Client::STATUS_ON_HOLD) {
            $paymentMethodInstance->process($order->getIncrementId(), $transactionId);
        } else {
            $paymentMethodInstance->sendOrderEmail($order, !$order->getEmailSent());
        }

        return $this->orderRepository->get($order->getId());
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @throws LocalizedException
     */
    protected function updateCustomerInfo(\Magento\Quote\Model\Quote $quote)
    {
        try {
            $customer = $this->customerRepository->get($quote->getCustomerEmail());
            $quote->updateCustomerData($customer);
        } catch (NoSuchEntityException $e) {
            $quote->setCustomerIsGuest(true);
        }
    }

    /**
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

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\DataObject $dinteroTransaction
     * @return bool
     */
    private function canInvoice($order, $dinteroTransaction)
    {
        if (!$order->canInvoice()) {
            return false;
        }

        if ($dinteroTransaction->getStatus() == Client::STATUS_AUTHORIZED
            && !$this->configHelper->canCreateInvoice()
        ) {
            return false;
        }

        return $dinteroTransaction->getStatus() != Client::STATUS_ON_HOLD;
    }
}
