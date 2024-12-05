<?php

namespace Dintero\Checkout\Controller\Payment;

use Dintero\Checkout\Helper\Config;
use Dintero\Checkout\Model\Agreements\Validator;
use Dintero\Checkout\Model\Api\Client;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

/**
 * Class SessionController
 *
 * @package Dintero\Checkout\Controller
 */
class Place extends Action
{
    /**
     * Client
     *
     * @var Client $client
     */
    protected $client;

    /**
     * Onepage checkout
     *
     * @var Onepage $onepageCheckout
     */
    protected $onepageCheckout;

    /**
     * Cart management
     *
     * @var CartManagementInterface $cartManagement
     */
    protected $cartManagement;

    /**
     * Order repository
     *
     * @var OrderRepositoryInterface $orderRepository
     */
    protected $orderRepository;

    /**
     * Result builder
     *
     * @var JsonFactory $resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Logger
     *
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var Config $configHelper
     */
    protected $configHelper;

    /**
     * @var Validator $agreementsValidator
     */
    protected $agreementsValidator;

    /**
     * SessionController constructor.
     *
     * @param Context $context
     * @param Client $client
     * @param Onepage $onepageCheckout
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     * @param Config $configHelper
     */
    public function __construct(
        Context $context,
        Client $client,
        Onepage $onepageCheckout,
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository,
        JsonFactory $resultJsonFactory,
        LoggerInterface $logger,
        Config $configHelper,
        Validator $agreementsValidator
    ) {
        parent::__construct($context);
        $this->client = $client;
        $this->onepageCheckout = $onepageCheckout;
        $this->cartManagement = $cartManagement;
        $this->orderRepository = $orderRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        $this->agreementsValidator = $agreementsValidator;
    }

    /**
     * Controller action which has to be returning dintero checkout url
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = new DataObject();
        try {
            $this->onepageCheckout->getCheckoutMethod();
            if (!$this->agreementsValidator->validate($this->_getCheckout()->getQuote()->getPayment())) {
                throw new LocalizedException( __(
                    "The order wasn't placed. "
                    . "First, agree to the terms and conditions, then try placing your order again."
                ));
            }

            $this->_getCheckout()
                ->getQuote()
                ->setDinteroGeneratorCode($this->configHelper->getLineIdFieldName())
                ->save();

            $orderId = $this->cartManagement->placeOrder($this->_getCheckout()->getQuote()->getId());
            $result->setData('success', true);

            $this->_eventManager->dispatch('checkout_dintero_checkout_placeOrder', [
                'result' => $result,
                'action' => $this
            ]);

            $order = $this->orderRepository->get($orderId);
            $data = $this->client->initCheckout($order);

            if (!empty($data['error']) && $data['error']['code'] == 'INVALID_REQUEST_PARAMETER' ) {
                throw new LocalizedException(__($this->_processErrors($data['error']['errors'])));
            }

            if (!isset($data['url'])) {
                throw new \Exception('Something went wrong');
            }

            $order->getPayment()->setAdditionalInformation('session_id', $data['id'] ?? null);
            $this->orderRepository->save($order);
            $data['url'] = $this->configHelper->resolveCheckoutUrl($data['url']);
            $data = array_merge(['success' => true], $data);
        } catch (LocalizedException $e) {
            $data = ['success' => false, 'error' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $data = ['success' => false, 'error' => __('Something went wrong')];
        }

        return $this->resultJsonFactory->create()->setData($data);
    }

    /**
     * Get checkout model
     *
     * @return CheckoutSession
     */
    protected function _getCheckout()
    {
        return $this->_objectManager->get(CheckoutSession::class);
    }

    /**
     * @param array $errors
     * @return string
     */
    private function _processErrors($errors)
    {
        $errors = array_map(function($error) {
            return $error['description'] ?? null;
        }, $errors);
        return implode(PHP_EOL, array_unique($errors));
    }
}
