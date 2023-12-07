<?php

namespace Dintero\Checkout\Plugin;

use Dintero\Checkout\Model\Dintero;

class CouponManagementPlugin
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Dintero\Checkout\Api\SessionManagementInterface $sessionManagement
     */
    protected $sessionManagement;

    /**
     * Define class dependencies
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Dintero\Checkout\Api\SessionManagementInterface $sessionManagement
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface       $cartRepository,
        \Dintero\Checkout\Api\SessionManagementInterface $sessionManagement
    ) {
        $this->quoteRepository = $cartRepository;
        $this->sessionManagement = $sessionManagement;
    }

    /**
     * Updating session in Dintero
     *
     * @param string|integer $cartId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function updateSession($cartId)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($cartId);

        if ($quote->getPayment()->getAdditionalInformation('id')) {
            $this->sessionManagement->updateSession($quote);
        }
    }

    /**
     * Updating session
     *
     * @param \Magento\Quote\Api\CouponManagementInterface|\Magento\Quote\Api\GuestCouponManagementInterface $subject
     * @param bool $result
     * @param string|integer $cartId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterSet($subject, $result, $cartId)
    {
        $this->updateSession($cartId);
        return $result;
    }

    /**
     * Remove coupon code
     *
     * @param \Magento\Quote\Api\CouponManagementInterface|\Magento\Quote\Api\GuestCouponManagementInterface $subject
     * @param bool $result
     * @param string $cartId
     * @return bool
     */
    public function afterRemove($subject, $result, $cartId)
    {
        $this->updateSession($cartId);
        return $result;
    }
}
