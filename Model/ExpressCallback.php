<?php

namespace Dintero\Checkout\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\OrderFactory;

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
     * @var ObjectManagerInterface $objectManager
     */
    protected $objectManager;

    /**
     * @var OrderFactory|\Magento\Sales\Model\OrderFactory $orderFactory
     */
    protected $orderFactory;

    /**
     * @var CreateOrder $createOrder
     */
    protected $createOrder;

    /**
     * @var DinteroFactory $paymentMethodFactory
     */
    protected $paymentMethodFactory;

    /**
     * ExpressCallback constructor.
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     * @param DataObjectFactory $dataObjectFactory
     * @param Quote $quoteResource
     * @param QuoteFactory $quoteFactory
     * @param ObjectManagerInterface $objectManager
     * @param OrderFactory $orderFactory
     * @param CreateOrder $createOrder
     * @param DinteroFactory $createOrder
     */
    public function __construct(
        LoggerInterface $logger,
        RequestInterface $request,
        SerializerInterface $serializer,
        DataObjectFactory $dataObjectFactory,
        Quote $quoteResource,
        QuoteFactory $quoteFactory,
        ObjectManagerInterface $objectManager,
        OrderFactory $orderFactory,
        CreateOrder $createOrder,
        DinteroFactory $paymentMethodFactory
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->serializer = $serializer;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->quoteResource = $quoteResource;
        $this->quoteFactory = $quoteFactory;
        $this->objectManager = $objectManager;
        $this->orderFactory = $orderFactory;
        $this->createOrder = $createOrder;
        $this->paymentMethodFactory = $paymentMethodFactory;
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

            if (!$request->getMerchantReference()) {
                $request->setMerchantReference($this->request->getParam('merchant_reference'));
            }

            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderFactory->create()->loadByIncrementId($request->getMerchantReference());
            if ($order->getId()) {
                $this->paymentMethodFactory->create()->process($order->getIncrementId(), $request->getId());
                return;
            }

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->quoteFactory->create();
            $this->quoteResource->load($quote, $request->getMerchantReference(), 'reserved_order_id');

            if (!$quote->getIsActive()) {
                throw new \Exception(__('Quote is not valid'));
            }
            $this->createOrder->createFromTransaction($quote, $request->getId());
            return;
        } catch (\Dintero\Checkout\Exception\PaymentCancelException $e) {

            $this->logger->error(sprintf(
                'Payment failed for order %s. Cancellation error: %s',
                $request->getMerchantReference(),
                $e->getMessage()
            ));

            // clear session id as after canceled payment session has to be re-initialized
            $quote->getPayment()->unsAdditionalInformation('id');
            $this->quoteResource->save($quote);

        } catch (\Dintero\Checkout\Exception\PaymentException $e) {
            $this->logger->error(sprintf(
                'Payment failed. Order id: %s. Error: %s. Request Body: %s',
                $request->getMerchantReference(),
                $e->getMessage(),
                $this->request->getContent()
            ));
        } catch (\Exception $e) {
            $this->logger->critical(sprintf(
                'Could not create order on callback. Order id: %s. Error: %s. Request Body: %s',
                $request->getMerchantReference(),
                $e->getMessage(),
                $this->request->getContent()
            ));
            throw $e;
        }
    }
}
