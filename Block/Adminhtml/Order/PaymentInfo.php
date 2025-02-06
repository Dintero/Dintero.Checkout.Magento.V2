<?php

namespace Dintero\Checkout\Block\Adminhtml\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Dintero\Checkout\Helper\Config;

class PaymentInfo extends Template
{

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * @var $coreRegistry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * Define class dependencies
     *
     * @param Context $context
     * @param Config $config
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->coreRegistry = $registry;
    }

    /**
     * Removing -capture, -refund, -void suffix from transaction id
     *
     * @param string $transactionId
     * @return string
     */
    protected function normalizeTransactionId($transactionId)
    {
        $endPosition = strpos($transactionId, '-');
        if ($endPosition === false) {
            return $transactionId;
        }
        return substr($transactionId, 0, $endPosition);
    }

    /**
     * Retrieve Order
     *
     * @return \Magento\Sales\Model\Order|null
     */
    public function getOrder()
    {
        if ($this->order === null) {
            if ($this->hasData('order')) {
                $this->order = $this->_getData('order');
            } elseif ($this->coreRegistry->registry('current_order')) {
                $this->order = $this->coreRegistry->registry('current_order');
            } elseif ($this->getParentBlock()->getOrder()) {
                $this->order = $this->getParentBlock()->getOrder();
            }
        }
        return $this->order;
    }

    /**
     * Retrieve payment info
     *
     * @return false|float|\Magento\Framework\DataObject|\Magento\Sales\Api\Data\OrderPaymentInterface|mixed|null
     */
    public function getPaymentInfo()
    {
        return $this->getOrder()->getPayment();
    }

    /**
     * Retrieve latest transaction status
     *
     * @return string|null
     */
    public function getPaymentStatus()
    {

        try {

            if (!$payment = $this->getPaymentInfo()) {
                return __('Unavailable');
            }

            $transactionId = $this->normalizeTransactionId($payment->getLastTransId());

            if (empty($transactionId)) {
                return __('Unavailable');
            }

            $paymentMethod = $payment->getMethodInstance();
            $response = $paymentMethod->fetchTransactionInfo($payment, $transactionId);
            return $response['status'] ?? __('Unavailable');
        } catch (\Throwable $e) {
            return __('Unavailable');
        }
    }

    /**
     * Retrieve Dintero transaction URL
     *
     * @return string
     */
    public function getTransactionUrl()
    {
        return sprintf(
            'https://backoffice.dintero.com/%s/payments/transactions/%s',
            $this->config->getFullAccountId($this->getOrder()->getStoreId()),
            $this->normalizeTransactionId($this->getPaymentInfo()->getLastTransId()),
        );
    }
}
