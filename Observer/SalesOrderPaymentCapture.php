<?php

namespace Dintero\Checkout\Observer;

use Dintero\Checkout\Model\Dintero as DinteroCheckout;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class SalesOrderPaymentCapture implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Sales Order Payment Place Start Observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var OrderPaymentInterface $payment */
        $payment = $observer['payment'];

        /** @var InvoiceInterface $invoice */
        $invoice = $observer['invoice'];

        if ($payment->getMethod() == DinteroCheckout::METHOD_CODE && $payment && $invoice) {
            $payment->setSalesDocument($invoice);
        }
    }
}
