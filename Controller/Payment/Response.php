<?php

namespace Dintero\Checkout\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Dintero\Checkout\Model\DinteroFactory;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Response Handler
 *
 * @package Dintero\Checkout\Controller
 */
class Response extends Action
{
    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * Payment factory
     *
     * @var DinteroFactory $paymentMethodFactory
     */
    protected $paymentMethodFactory;

    /**
     * Response constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context,
        DinteroFactory $paymentMethodFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->logger = $logger;
    }

    /**
     * Handling response
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function execute()
    {
        $merchantOrderId = $this->getRequest()->getParam('merchant_reference');
        $transactionId = $this->getRequest()->getParam('transaction_id');
        $sessionId = $this->getRequest()->getParam('session_id');
        if (empty($merchantOrderId) || empty($transactionId) || empty($sessionId)) {
            $logData = [
                'request_uri' => $this->getRequest()->getServer('REQUEST_URI'),
                'params' => $this->getRequest()->getParams(),
            ];
            $this->logger->error(var_export($logData, true));
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                'status' => 'error',
                'message' => __('Missing required params')
            ]);

        }

        $this->paymentMethodFactory->create()->process($merchantOrderId, $transactionId, $sessionId);
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
            'status' => 'ok',
            'message' => __('Success')
        ]);
    }
}
