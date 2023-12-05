<?php

namespace Dintero\Checkout\Gateway\Command;

use Dintero\Checkout\Model\Api\Client;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\CommandInterface;

/**
 * Class CaptureCommand
 *
 * @package Dintero\Checkout\Gateway\Command
 */
class CaptureCommand implements CommandInterface
{
    /**
     * API client for dintero
     *
     * @var Client $api
     */
    private $api;

    /**
     * Registry
     *
     * @var Registry $registry
     */
    private $registry;

    /**
     * Capture constructor.
     *
     * @param Client $client
     * @param Registry $registry
     */
    public function __construct(
        Client $client,
        Registry $registry
    ) {
        $this->api = $client;
        $this->registry = $registry;
    }

    /**
     * Executing command
     *
     * @param array $commandSubject
     * @return $this|\Magento\Payment\Gateway\Command\ResultInterface|null
     * @throws \Exception
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $commandSubject['payment']->getPayment();

        $invoice = $this->registry->registry('current_invoice');
        if ($invoice) {
            $payment->setSalesDocument($invoice);
        }

        $transactionId = $payment->getTransactionId();

        if ($payment->getAuthorizationTransaction()) {
            $transactionId = $payment->getAuthorizationTransaction()->getTxnId();
        } elseif ($invoice) {
            $transactionId = $invoice->getTransactionId();
        }

        $transactionInfo = $this->api->getTransaction($transactionId);

        // If the transaction is in any post-capture state, we allow the completion of the order
        if (!empty($transactionInfo['status']) && in_array($transactionInfo['status'], array(Client::STATUS_CAPTURED, Client::STATUS_PARTIALLY_CAPTURED, Client::STATUS_PARTIALLY_REFUNDED, Client::STATUS_REFUNDED, Client::STATUS_PARTIALLY_CAPTURED_REFUNDED))) {
            return $this;
        }

        $result = $this->api->capture(
            $transactionId,
            $payment,
            $commandSubject['amount']
        );

        if (!is_array($result) || isset($result['error'])) {
            throw new \Exception(__('Failed to capture the payment'));
        }

        return $this;
    }
}
