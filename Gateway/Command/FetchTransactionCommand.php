<?php

namespace Dintero\Checkout\Gateway\Command;

use Dintero\Checkout\Model\Api\Client;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class VoidCommand
 *
 * @package Dintero\Checkout\Gateway\Command
 */
class FetchTransactionCommand implements CommandInterface
{
    /**
     * API client for dintero
     *
     * @var Client $api
     */
    private $api;

    /**
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
        \Magento\Framework\Registry $registry
    ) {
        $this->api = $client;
        $this->registry = $registry;
    }

    /**
     * Updating transaction type
     *
     * @param array $responseData
     * @return void
     */
    private function updateTransactionType($responseData)
    {
        /** @var Transaction $transaction */
        $transaction = $this->registry->registry('current_transaction');

        if ($transaction
            && $transaction->getTxnType() === Transaction::TYPE_ORDER
            && $responseData['status'] === Client::STATUS_AUTHORIZED
        ) {
            $transaction->setTxnType(Transaction::TYPE_AUTH);
        }
    }

    /**
     * Fetching transaction information
     *
     * @param array $commandSubject
     * @return array
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $result = $this->api->getTransaction(
            $commandSubject['transactionId'],
            $payment->getOrder()->getStoreId()
        );

        if (isset($result['error'])) {
            throw new \Exception(__('Failed to void the transaction'));
        }

        $payment->setTransactionId($commandSubject['transactionId']);
        $this->updateTransactionType($result);
        $data = [];
        foreach ($result as $field => $value) {
            if (is_string($value)) {
                $data[$field] = $value;
            }
        }
        return $data;
    }
}
