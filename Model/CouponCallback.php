<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Api\Data\OrderInterfaceFactory;
use Dintero\Checkout\Api\Data\Shipping\RequestInterfaceFactory;
use Dintero\Checkout\Api\Data\Shipping\ResponseInterfaceFactory;
use Dintero\Checkout\Api\Discount\RuleManagementInterface;
use Dintero\Checkout\Api\ShippingManagementInterface;
use Dintero\Checkout\Model\Api\Request\Builder\DiscountLineBuilder;
use Dintero\Checkout\Model\Api\Request\Builder\OrderItemBuilder;
use Dintero\Checkout\Model\Api\Request\Builder\ShippingOptionBuilder;
use Dintero\Checkout\Model\Api\Request\Builder\GiftCardItemBuilder;
use Dintero\Checkout\Model\Api\Request\LineIdGenerator;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;

class CouponCallback extends ShippingCallback implements \Dintero\Checkout\Api\CouponCallbackInterface
{

    /** @var ShippingManagementInterface $shippingOptionManagement */
    protected $shippingOptionManagement;

    /**
     * Define class dependencies
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param SerializerInterface $serializer
     * @param ResponseInterfaceFactory $responseFactory
     * @param LoggerInterface $logger
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param RequestInterfaceFactory $requestFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param QuoteFactory $quoteFactory
     * @param ShippingMethodManagementInterface $shippingMethodManagement
     * @param ShippingOptionBuilder $shippingOptionBuilder
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderItemBuilder $orderItemBuilder
     * @param LineIdGenerator $lineIdGenerator
     * @param RuleManagementInterface $ruleManagement
     * @param DiscountLineBuilder $discountLineBuilder
     * @param ShippingManagementInterface $shippingOptionManagement
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        SerializerInterface $serializer,
        ResponseInterfaceFactory $responseFactory,
        LoggerInterface $logger,
        DataObjectHelper $dataObjectHelper,
        DataObjectFactory $dataObjectFactory,
        RequestInterfaceFactory $requestFactory,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        QuoteFactory $quoteFactory,
        ShippingMethodManagementInterface $shippingMethodManagement,
        ShippingOptionBuilder $shippingOptionBuilder,
        OrderInterfaceFactory $orderFactory,
        OrderItemBuilder $orderItemBuilder,
        LineIdGenerator $lineIdGenerator,
        RuleManagementInterface $ruleManagement,
        DiscountLineBuilder $discountLineBuilder,
        ShippingManagementInterface $shippingOptionManagement,
        GiftCardItemBuilder $giftCardItemBuilder
    ) {
        parent::__construct(
            $request,
            $serializer,
            $responseFactory,
            $logger,
            $dataObjectHelper,
            $dataObjectFactory,
            $requestFactory,
            $quoteResource,
            $quoteFactory,
            $shippingMethodManagement,
            $shippingOptionBuilder,
            $orderFactory,
            $orderItemBuilder,
            $lineIdGenerator,
            $ruleManagement,
            $discountLineBuilder,
            $giftCardItemBuilder
        );
        $this->shippingOptionManagement = $shippingOptionManagement;
    }

    /**
     * Calculate order total
     *
     * @param Quote $quote
     * @return float|int
     */
    protected function calculateOrderTotal(Quote $quote)
    {
        return $quote->getBaseGrandTotal() * 100;
    }

    /**
     * Retrieve coupon options
     *
     * @return \Dintero\Checkout\Api\Data\Shipping\ResponseInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function getOptions()
    {
        /** @var \Dintero\Checkout\Api\Data\Shipping\RequestInterface $request */
        $request = $this->requestFactory->create();
        $requestBody = $this->dataObjectFactory->create()
            ->setData($this->serializer->unserialize($this->request->getContent()));
        $this->objectHelper->populateWithArray(
            $request,
            array_merge($requestBody->getData(), $requestBody->getData('order')),
            \Dintero\Checkout\Api\Data\Shipping\RequestInterface::class
        );

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();
        $this->quoteResource->load($quote, $request->getMerchantReference(), 'reserved_order_id');

        if (!$quote->getIsActive()) {
            throw new LocalizedException(__('Quote is not valid'));
        }

        $couponCode = current($request->getDiscountCodes() ?? []) ?: '';
        $quote->setCouponCode($couponCode);

        $quote->getShippingAddress()
            ->setPostcode($request->getShippingAddress()->getPostalCode())
            ->setStreetFull($request->getShippingAddress()->getAddressLine())
            ->setFirstname($request->getShippingAddress()->getFirstName())
            ->setLastname($request->getShippingAddress()->getLastName())
            ->setCountryId($request->getShippingAddress()->getCountry())
            ->setEmail($request->getShippingAddress()->getEmail())
            ->setCity($request->getShippingAddress()->getPostalPlace())
            ->setTelephone($request->getShippingAddress()->getPhoneNumber())
            ->setCollectShippingRates(true)
            ->setTotalsCollected(false);
        $quote->collectTotals();

        $this->quoteResource->save($quote);
        $shippingMethods = $this->shippingMethodManagement->getList($quote->getId());

        $shippingOptions = [];

        /** @var \Magento\Quote\Model\Cart\ShippingMethod $shippingMethod */
        foreach ($shippingMethods as $shippingMethod) {
            $shippingOption = $this->shippingOptionBuilder->build($shippingMethod);
            array_push($shippingOptions, $shippingOption);
        }

        $order = $this->prepareOrder($quote);

        if ($selectedShippingOption = $this->shippingOptionManagement->getSelectedShippingOptionByQuote($quote)) {
            $order->setAmount($order->getAmount() - $selectedShippingOption->getAmount());
            $selectedShippingOption->setAmount($quote->getShippingAddress()->getBaseShippingAmount() * 100);
            $order->setShippingOption($selectedShippingOption);
        }

        return $this->responseFactory->create()
            ->setShippingOptions($shippingOptions)
            ->setOrder($order);
    }
}
