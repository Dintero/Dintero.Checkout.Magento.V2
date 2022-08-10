<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Api\SessionManagementInterface;
use Dintero\Checkout\Model\Api\Client;
use Dintero\Checkout\Model\Api\ClientFactory;

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
     * Session constructor.
     *
     * @param ClientFactory $clientFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        ClientFactory                                      $clientFactory,
        \Dintero\Checkout\Api\Data\SessionInterfaceFactory $sessionFactory,
        \Magento\Checkout\Model\Session                    $checkoutSession,
        \Magento\Framework\DataObjectFactory               $dataObjectFactory
    ) {
        $this->client = $clientFactory->create()->setType(Client::TYPE_EMBEDDED);
        $this->sessionFactory = $sessionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->objectFactory = $dataObjectFactory;
    }

    /**
     * Cancel current active session in Dintero
     *
     * @param string $sessionId
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    private function checkSession($sessionId)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        $sessionInfo = $this->client->getSessionInfo($sessionId);
        $responseObject = $this->objectFactory->create()->setData($sessionInfo);

        if (!$responseObject->getId()
            || $responseObject->getData('order/merchant_reference') != $quote->getReservedOrderId()) {
            return null;
        }

        if ($quote->getGrandTotal() != ($responseObject->getData('order/amount')/100)) {
            return null;
        }

        return $responseObject->getId();
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
        if ($sessionId = $this->checkSession($dinteroSessionId)) {
            return $this->sessionFactory->create()->setId($sessionId);
        }

        if ($dinteroSessionId) {
            $this->cancelSession($dinteroSessionId);
        }

        $response = $this->client
            ->setType(Client::TYPE_EMBEDDED)
            ->initSessionFromQuote($quote);
        $quote->getPayment()->setAdditionalInformation($response)->save();
        return $this->sessionFactory->create()->setId($response['id'] ?? null);
    }
}
