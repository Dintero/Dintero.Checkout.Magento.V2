<?php

namespace Dintero\Checkout\Model\Gateway\Http;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

/**
 * Class Client
 *
 * @package Dintero\Checkout\Model\Api
 */
class Client implements ClientInterface
{
    /*
     * Post method
     */
    const METHOD_POST = 'POST';

    /*
     * Method GET
     */
    const METHOD_GET = 'GET';

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory|\Magento\Framework\HTTP\LaminasClientFactory
     */
    private $clientFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Serializer
     *
     * @var Json $serializer
     */
    private $serializer;

    /**
     * Class constructor
     *
     * @param Logger $logger
     * @param Json $serializer
     */
    public function __construct(
        Logger $logger,
        Json $serializer
    ) {

        $className = '\Magento\Framework\HTTP\ZendClientFactory';
        if (class_exists('\Magento\Framework\HTTP\LaminasClient')
            && class_exists('\Magento\Framework\HTTP\LaminasClientFactory')
        ) {
            $className = '\Magento\Framework\HTTP\LaminasClientFactory';
        }

        $this->clientFactory = \Magento\Framework\App\ObjectManager::getInstance()->get($className);

        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * {inheritdoc}
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $log = [
            'request' => $transferObject->getBody(),
            'request_uri' => $transferObject->getUri()
        ];

        $result = [];

        /** @var \Magento\Framework\HTTP\ZendClient|\Magento\Framework\HTTP\LaminasClient $client */
        $client = $this->clientFactory->create();

        $client->setMethod($transferObject->getMethod());
        $client->setHeaders($transferObject->getHeaders());
        $client->setUrlEncodeBody($transferObject->shouldEncode());
        $client->setUri($transferObject->getUri());
        if (!empty($transferObject->getAuthUsername())) {
            $client->setAuth($transferObject->getAuthUsername(), $transferObject->getAuthPassword());
        }

        $isLaminas = !($client instanceof \Magento\Framework\HTTP\ZendClient);

        switch ($transferObject->getMethod()) {
            case self::METHOD_GET:
                $client->setParameterGet($transferObject->getBody());
                break;
            case self::METHOD_POST && $isLaminas:
                $client->setRawBody($this->serializer->serialize($transferObject->getBody()));
                break;
            case self::METHOD_POST && !$isLaminas:
                $client->setRawData($this->serializer->serialize($transferObject->getBody()));
                break;
            default:
                throw new \LogicException(
                    sprintf(
                        __('Unsupported HTTP method %s'),
                        $transferObject->getMethod()
                    )
                );
        }

        if ($transferObject->getAuthUsername() && $transferObject->getAuthPassword()) {
            $client->setAuth($transferObject->getAuthUsername(), $transferObject->getAuthPassword());
        }

        $client->setHeaders($transferObject->getHeaders());
        $client->setUrlEncodeBody($transferObject->shouldEncode());
        $client->setUri($transferObject->getUri());

        try {
            if ($isLaminas) {
                /** @var \Laminas\Http\Client $response */
                $client->setAdapter(\Dintero\Checkout\Model\Gateway\Http\CurlAdapter::class);
                $response = $client->send();
            } else {
                $response = $client->request();
            }

            $result = $this->serializer ? $this->serializer->unserialize($response->getBody()) : [$response->getBody()];
            $log['response'] = $result;
        } catch (\Magento\Payment\Gateway\Http\ConverterException $e) {
            throw $e;
        } finally {
            $this->logger->debug($log);
        }

        return $result;
    }
}
