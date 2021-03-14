<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Helper\Config;
use Dintero\Checkout\Model\Api\Client;
use Dintero\Checkout\Model\Payment\TransactionStatusResolver;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Psr\Log\LoggerInterface;
use Magento\Framework\Registry;

/**
 * Class ExpressCallback
 *
 * @package Dintero\Checkout\Model
 */
class ExpressCallback implements \Dintero\Checkout\Api\ExpressCallbackInterface
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
     * @var Quote $quoteResource
     */
    protected $quoteResource;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CartManagementInterface $cartManagement
     */
    protected $cartManagement;

    /**
     * @var AddressMapperFactory $addressMapperFactory
     */
    protected $addressMapperFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Builder
     */
    protected $transactionBuilder;

    /**
     * @var Config $configHelper
     */
    protected $configHelper;

    /**
     * @var TransactionStatusResolver $transactionStatusResolver
     */
    protected $transactionStatusResolver;

    /**
     * @var InvoiceManagementInterface $invoiceManagement
     */
    protected $invoiceManagement;

    /**
     * @var ObjectManagerInterface $objectManager
     */
    protected $objectManager;

    /**
     * @var Registry $registry
     */
    protected $registry;

    /**
     * @var InvoiceRepositoryInterface $invoiceRepository
     */
    protected $invoiceRepository;

    /**
     * ExpressCallback constructor.
     *
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     * @param DataObjectFactory $dataObjectFactory
     * @param Quote $quoteResource
     * @param QuoteFactory $quoteFactory
     * @param CartManagementInterface $cartManagement
     * @param AddressMapperFactory $addressMapperFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param Builder $transactionBuilder
     * @param Config $configHelper
     * @param TransactionStatusResolver $transactionStatusResolver
     * @param InvoiceManagementInterface $invoiceManagement
     * @param ObjectManagerInterface $objectManager
     * @param Registry $registry
     * @param InvoiceRepositoryInterface $invoiceRepository
     */
    public function __construct(
        LoggerInterface $logger,
        RequestInterface $request,
        SerializerInterface $serializer,
        DataObjectFactory $dataObjectFactory,
        Quote $quoteResource,
        QuoteFactory $quoteFactory,
        CartManagementInterface $cartManagement,
        AddressMapperFactory $addressMapperFactory,
        CustomerRepositoryInterface $customerRepository,
        Builder $transactionBuilder,
        Config $configHelper,
        TransactionStatusResolver $transactionStatusResolver,
        InvoiceManagementInterface $invoiceManagement,
        ObjectManagerInterface $objectManager,
        Registry $registry,
        InvoiceRepositoryInterface $invoiceRepository
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->serializer = $serializer;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->quoteResource = $quoteResource;
        $this->quoteFactory = $quoteFactory;
        $this->cartManagement = $cartManagement;
        $this->addressMapperFactory = $addressMapperFactory;
        $this->customerRepository = $customerRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->configHelper = $configHelper;
        $this->transactionStatusResolver = $transactionStatusResolver;
        $this->invoiceManagement = $invoiceManagement;
        $this->objectManager = $objectManager;
        $this->registry = $registry;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * @return mixed|void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
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

            if (!$quote->getIsActive()) {
                throw new \Exception(__('Quote is not valid'));
            }

            // populating billing address with data from dintero
            $this->addressMapperFactory
                ->create(['address' => $quote->getBillingAddress(), 'dataObject' => $request])
                ->map();

            if (!$quote->isVirtual()) {
                // populating shipping address data from dintero
                $this->addressMapperFactory
                    ->create(['address' => $quote->getShippingAddress(), 'dataObject' => $request])
                    ->map();
                $quote->getShippingAddress()->setShippingMethod($request->getData('shipping_option/id'));
            }

            $quote->setCustomerEmail(
                !$quote->isVirtual() ? $quote->getBillingAddress()->getEmail() : $quote->getShippingAddress()->getEmail()
            );

            $quote->getPayment()->setMethod(Dintero::METHOD_CODE);
            $this->updateCustomerInfo($quote);
            $quote->collectTotals();
            $this->quoteResource->save($quote);

            /** @var Order $order */
            $order = $this->cartManagement->submit($quote);

            $paymentObject = $order->getPayment();
            $paymentObject->setCcTransId($request->getId())
                ->setLastTransId($request->getId());
            $transaction = $this->transactionBuilder->setPayment($paymentObject)
                ->setOrder($order)
                ->setTransactionId($request->getId())
                ->build($this->transactionStatusResolver->resolve(Client::STATUS_AUTHORIZED));

            $transaction->setIsClosed(false)->save();

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
            $this->logger->info($this->request->getContent());
            throw $e;
        }
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
}
