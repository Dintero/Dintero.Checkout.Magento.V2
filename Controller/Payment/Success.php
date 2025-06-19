<?php

namespace Dintero\Checkout\Controller\Payment;

use Dintero\Checkout\Model\Api\Client;
use Dintero\Checkout\Model\CreateOrder;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Dintero\Checkout\Model\DinteroFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Success
 *
 * @package Dintero\Checkout\Controller\Payment
 */
class Success extends Action
{
    /**
     * Order factory
     *
     * @var OrderFactory $orderFactory
     */
    protected $orderFactory;

    /**
     * @var Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CreateOrder $createOrder
     */
    protected $createOrder;

    /**
     * @var DinteroFactory $paymentMethodFactory
     */
    private $paymentMethodFactory;

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param QuoteFactory $quoteFactory
     * @param CreateOrder $createOrder
     * @param DinteroFactory $paymentMethodFactory
     * @param Client $client
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        QuoteFactory $quoteFactory,
        CreateOrder $createOrder,
        DinteroFactory $paymentMethodFactory,
        Client $client,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
        $this->createOrder = $createOrder;
        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Processing response
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $result = $this->resultRedirectFactory->create();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderFactory->create()
            ->loadByIncrementId($this->getRequest()->getParam('merchant_reference'));

        $transactionId = $this->getRequest()->getParam('transaction_id');

        // processing standard checkout
        if ($transactionId && $order->getId()) {
            $this->paymentMethodFactory->create()->process($order->getIncrementId(), $transactionId);
        }

        // processing express and embedded checkout
        if ($transactionId && !$order->getId()) {

            try {
                $order = $this->createOrder->createFromTransaction($this->checkoutSession->getQuote(), $transactionId);
            } catch (\Dintero\Checkout\Exception\PaymentException $e) {
                $this->logger->error(sprintf(
                    'Could not create order from transaction %s. Error: %s',
                    $transactionId,
                    $e->getMessage()
                ));
                $this->messageManager->addErrorMessage(__('Payment failed'));
                return $result->setPath('checkout/cart');
            } catch (\Exception $e) {
                $this->logger->critical(sprintf(
                    'Could not create order from transaction %s. Error: %s',
                    $transactionId,
                    $e->getMessage()
                ));
                $this->messageManager->addErrorMessage(__('Something went wrong. Could not place order.'));
                return $result->setPath('checkout/cart');
            }
        }

        if ($order->getId()) {
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId())
                ->setLastQuoteId($order->getQuoteId())
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());
        }

        if ($order->getId() && $transactionId) {
            return $result->setPath('checkout/onepage/success');
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        if ($order->getId() && $order->canCancel() && !$payment->getAuthorizationTransaction()) {

            if ($sessionId = $order->getPayment()->getAdditionalInformation('session_id')) {
                $this->client->cancelSession($sessionId);
            }

            $order->getPayment()
                ->setTransactionId(null)
                ->cancel();

            $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
            $quote->setIsActive(true)->setReservedOrderId(null)->save();
            $this->checkoutSession->replaceQuote($quote);

            $order->registerCancellation('Payment Failed')->save();
            $this->_eventManager->dispatch('order_cancel_after', ['order' => $order ]);
        }
        $this->messageManager->addErrorMessage(__('Payment failed'));
        return $result->setPath('checkout/cart');
    }
}
