<?php

namespace Dintero\Checkout\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\OrderFactory;

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
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var Quote $quoteResource
     */
    protected $quoteResource;

    /**
     * @var ObjectManagerInterface $objectManager
     */
    protected $objectManager;

    /** @var \Magento\Sales\Model\OrderFactory */
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
     * EmbeddedCallback constructor.
     *
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     * @param QuoteFactory $quoteFactory
     * @param Quote $quoteResource
     * @param DataObjectFactory $dataObjectFactory
     * @param ObjectManagerInterface $objectManager
     * @param OrderFactory $orderFactory
     * @param CreateOrder $createOrder
     */
    public function __construct(
        LoggerInterface $logger,
        RequestInterface $request,
        SerializerInterface $serializer,
        QuoteFactory $quoteFactory,
        Quote $quoteResource,
        DataObjectFactory $dataObjectFactory,
        ObjectManagerInterface $objectManager,
        OrderFactory $orderFactory,
        CreateOrder $createOrder,
        DinteroFactory $paymentMethodFactory
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->serializer = $serializer;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->quoteResource =  $quoteResource;
        $this->quoteFactory =  $quoteFactory;
        $this->objectManager = $objectManager;
        $this->orderFactory = $orderFactory;
        $this->createOrder = $createOrder;
        $this->paymentMethodFactory = $paymentMethodFactory;
    }

    /**
     * @return mixed|void
     */
    public function execute()
    {
        try {

            $request = $this->dataObjectFactory->create([
                'data' => $this->serializer->unserialize($this->request->getContent())
            ]);

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
            $sessionId = $quote->getPayment()->getAdditionalInformation('id');
            if (!$sessionId || $sessionId != $request->getSessionId()) {
                throw new \Exception(__('Quote is not valid'));
            }

            $this->createOrder->createFromTransaction($quote, $request->getId());
        } catch (\Dintero\Checkout\Exception\PaymentCancelException $e) {
            $this->logger->error(sprintf(
                'Payment failed for order %s. Cancellation error: %s',
                $request->getMerchantReference(),
                $e->getMessage()
            ));
            // clear session as after canceled payment session has to be re-initialized
            $quote->getPayment()->unsAdditionalInformation('id');
            $this->quoteResource->save($quote);
        } catch (\Dintero\Checkout\Exception\PaymentException $e) {
            $this->logger->error(sprintf(
                'Payment failed for order %s. Error: %s',
                $request->getMerchantReference(),
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw $e;
        }
    }
}
