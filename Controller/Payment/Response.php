<?php

namespace Dintero\Hp\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Dintero\Hp\Model\DinteroFactory;

/**
 * Class Response Handler
 *
 * @package Dintero\Hp\Controller
 */
class Response extends Action implements HttpGetActionInterface
{

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
        DinteroFactory $paymentMethodFactory
    ) {
        parent::__construct($context);
        $this->paymentMethodFactory = $paymentMethodFactory;
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
        $this->paymentMethodFactory->create()->process($merchantOrderId, $transactionId, $sessionId);
    }
}
