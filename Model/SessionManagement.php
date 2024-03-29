<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Api\SessionManagementInterface;
use Dintero\Checkout\Model\Api\Client;
use Dintero\Checkout\Model\Api\ClientFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Session
 *
 * @package Dintero\Checkout\Model
 */
class SessionManagement implements SessionManagementInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var
     */
    protected $sessionFactory;

    /**
     * @var \Magento\Framework\DataObjectFactory $objectFactory
     */
    protected $objectFactory;

    /**
     * @var \Magento\Framework\Encryption\Encryptor $encryptor
     */
    protected $encryptor;

    /**
     * Define class dependencies
     *
     * @param ClientFactory $clientFactory
     * @param \Dintero\Checkout\Api\Data\SessionInterfaceFactory $sessionFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     */
    public function __construct(
        ClientFactory                                      $clientFactory,
        \Dintero\Checkout\Api\Data\SessionInterfaceFactory $sessionFactory,
        \Magento\Checkout\Model\Session                    $checkoutSession,
        \Magento\Framework\DataObjectFactory               $dataObjectFactory,
        \Magento\Framework\Encryption\Encryptor             $encryptor
    ) {
        $this->client = $clientFactory->create()->setType(Client::TYPE_EMBEDDED);
        $this->sessionFactory = $sessionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->objectFactory = $dataObjectFactory;
        $this->encryptor = $encryptor;
    }

    /**
     * Cancel current active session in Dintero
     *
     * @param string $sessionId
     * @param \Magento\Quote\Model\Quote\Payment $payment
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    private function checkSession($sessionId, $payment)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        $sessionInfo = $this->client->getSessionInfo($sessionId);
        $responseObject = $this->objectFactory->create()->setData($sessionInfo);

        // validate order number
        if (!$responseObject->getId()
            || $responseObject->getData('order/merchant_reference') != $quote->getReservedOrderId()) {
            return null;
        }

        // validate total amount
        if ($quote->getGrandTotal() != ($responseObject->getData('order/amount')/100)) {
            return null;
        }

        // validate hash generated from quote
        if ($this->generateHash($payment->getQuote()) !== $payment->getAdditionalInformation('quote_hash')) {
            return null;
        }

        return $responseObject->getId();
    }

    /**
     * Generate hash for quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return string
     */
    private function generateHash(\Magento\Quote\Model\Quote $quote)
    {
        $data = [];
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            $data[] = implode(':', [$item->getSku(), $item->getQty()]);
        }
        if (!$quote->getIsVirtual()) {
            $data[] = $quote->getShippingAddress()->getShippingMethod();
        }
        return $this->encryptor->encryptWithFastestAvailableAlgorithm(implode('|', $data));
    }

    /**
     * Cancelling existing session by session id
     *
     * @param string $sessionId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    private function cancelSession($sessionId)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        $sessionInfo = $this->client->getSessionInfo($sessionId);
        $responseObject = $this->objectFactory->create()->setData($sessionInfo);

        if (!$responseObject->getId()
            || $responseObject->getData('order/merchant_reference') != $quote->getReservedOrderId()) {
            return ;
        }

        $this->client->cancelSession($sessionId);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function getSession()
    {
        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $dinteroSessionId = $payment->getAdditionalInformation('id');
        if ($sessionId = $this->checkSession($dinteroSessionId, $payment)) {
            return $this->sessionFactory->create()->setId($sessionId);
        }

        if ($dinteroSessionId) {
            $this->cancelSession($dinteroSessionId);
        }

        $response = $this->client
            ->setType(Client::TYPE_EMBEDDED)
            ->initSessionFromQuote($quote);
        $quote->getPayment()
            ->setAdditionalInformation($response)
            ->setAdditionalInformation('quote_hash', $this->generateHash($quote))
            ->save();
        return $this->sessionFactory->create()->setId($response['id'] ?? null);
    }

    /**
     * Update dintero session
     *
     * @return void
     */
    public function updateSession()
    {
        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $dinteroSessionId = $payment->getAdditionalInformation('id');
        $session = $this->sessionFactory->create();

        $sessionInfo = $this->client->getSessionInfo($dinteroSessionId);
        $responseObject = $this->objectFactory->create()->setData($sessionInfo);

        // validate order number
        if (!$responseObject->getId()
            || $responseObject->getData('order/merchant_reference') != $quote->getReservedOrderId()) {
            throw new LocalizedException(__('Could not validate dintero session.'));
        }

        $quote->reserveOrderId();
        $response = $this->client->updateSession($responseObject->getId(), $quote);

        $session->setId($response['id'] ?? null);

        return $session;
    }
}
