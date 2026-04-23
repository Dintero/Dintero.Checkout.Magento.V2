<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Model\Api\Request\Builder\OrderItemBuilder;
use Dintero\Checkout\Api\Data\OrderInterfaceFactory;
use Dintero\Checkout\Api\Data\Shipping\RequestInterface;
use Dintero\Checkout\Api\Data\Shipping\RequestInterfaceFactory;
use Dintero\Checkout\Api\Data\Shipping\ResponseInterfaceFactory;
use Dintero\Checkout\Api\Discount\RuleManagementInterface;
use Dintero\Checkout\Model\Api\Request\Builder\DiscountLineBuilder;
use Dintero\Checkout\Model\Api\Request\Builder\GiftCardItemBuilder;
use Dintero\Checkout\Model\Api\Request\Builder\ShippingOptionBuilder;
use Dintero\Checkout\Model\Api\Request\LineIdGenerator;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;
use Psr\Log\LoggerInterface;

class ShippingCallback implements \Dintero\Checkout\Api\ShippingCallbackInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var ResponseInterfaceFactory
     */
    protected $responseFactory;

    /**
     * @var DataObjectHelper
     */
    protected $objectHelper;

    /**
     * @var RequestInterfaceFactory
     */
    protected $requestFactory;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var Quote
     */
    protected $quoteResource;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var ShippingMethodManagementInterface
     */
    protected $shippingMethodManagement;

    /**
     * @var OrderInterfaceFactory $orderFactory
     */
    protected $orderFactory;

    /**
     * @var LineIdGenerator $lineIdGenerator
     */
    protected $lineIdGenerator;

    /**
     * @var RuleManagementInterface $ruleManagement
     */
    protected $ruleManagement;

    /**
     * @var DiscountLineBuilder $discountLineBuilder
     */
    protected $discountLineBuilder;

    /**
     * @var ShippingOptionBuilder $shippingOptionBuilder
     */
    protected $shippingOptionBuilder;

    /**
     * @var OrderItemBuilder $orderItemBuilder
     */
    protected $orderItemBuilder;

    /**
     * @var GiftCardItemBuilder $giftcardItemBuilder
     */
    protected $giftCardItemBuilder;

    /**
     * ShippingCallback constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param SerializerInterface $serializer
     * @param ResponseInterfaceFactory $responseFactory
     * @param LoggerInterface $logger
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param RequestInterfaceFactory $requestFactory
     * @param Quote $quoteResource
     * @param QuoteFactory $quoteFactory
     * @param ShippingMethodManagementInterface $shippingMethodManagement
     * @param ShippingOptionBuilder $shippingOptionBuilder
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderItemBuilder $orderItemBuilder
     * @param LineIdGenerator $lineIdGenerator
     * @param RuleManagementInterface $ruleManagement
     * @param DiscountLineBuilder $discountLineBuilder
     * @param GiftCardItemBuilder $giftCardItemBuilder
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        SerializerInterface $serializer,
        ResponseInterfaceFactory $responseFactory,
        LoggerInterface $logger,
        DataObjectHelper $dataObjectHelper,
        DataObjectFactory $dataObjectFactory,
        RequestInterfaceFactory $requestFactory,
        Quote $quoteResource,
        QuoteFactory $quoteFactory,
        ShippingMethodManagementInterface $shippingMethodManagement,
        ShippingOptionBuilder $shippingOptionBuilder,
        OrderInterfaceFactory $orderFactory,
        OrderItemBuilder $orderItemBuilder,
        LineIdGenerator $lineIdGenerator,
        RuleManagementInterface $ruleManagement,
        DiscountLineBuilder $discountLineBuilder,
        GiftCardItemBuilder $giftCardItemBuilder
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->responseFactory = $responseFactory;
        $this->objectHelper = $dataObjectHelper;
        $this->requestFactory = $requestFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->quoteResource = $quoteResource;
        $this->quoteFactory = $quoteFactory;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->shippingOptionBuilder = $shippingOptionBuilder;
        $this->orderFactory = $orderFactory;
        $this->orderItemBuilder = $orderItemBuilder;
        $this->lineIdGenerator = $lineIdGenerator;
        $this->ruleManagement = $ruleManagement;
        $this->discountLineBuilder = $discountLineBuilder;
        $this->giftCardItemBuilder = $giftCardItemBuilder;
    }

    /**
     * Retrieve shipping options
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
            RequestInterface::class
        );

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();
        $this->quoteResource->load($quote, $request->getMerchantReference(), 'reserved_order_id');

        if (!$quote->getIsActive()) {
            throw new LocalizedException(__('Quote is not valid'));
        }

        $couponCode = current($request->getDiscountCodes() ?? []) ?? null;
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
        if ($shippingOptionAmount = $requestBody->getData('order/shipping_option/amount')) {
            $order->setAmount($order->getAmount() + $shippingOptionAmount);
        }

        return $this->responseFactory->create()
            ->setShippingOptions($shippingOptions)
            ->setOrder($order);
    }

    /**
     * Calculate order total amoun
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return float|int
     */
    protected function calculateOrderTotal(\Magento\Quote\Model\Quote $quote)
    {
        $baseShippingAmount = $quote->getShippingAddress()->getBaseShippingAmount() * 100;
        $baseShippingTaxAmount = $quote->getShippingAddress()->getBaseShippingTaxAmount() * 100;
        $baseShippingTotalAmount = $baseShippingAmount + $baseShippingTaxAmount;
        $orderTotal = $quote->getBaseGrandTotal() * 100;
        $orderTotal -= $baseShippingTotalAmount;
        return $orderTotal;
    }

    /**
     * Prepare order object
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Dintero\Checkout\Api\Data\OrderInterface
     */
    protected function prepareOrder(\Magento\Quote\Model\Quote $quote)
    {
        /** @var \Dintero\Checkout\Api\Data\OrderInterface $order */
        $order = $this->orderFactory->create();

        $order->setAmount($this->calculateOrderTotal($quote))
            ->setCurrency($quote->getBaseCurrencyCode());
        $order->setDiscountCodes($quote->getCouponCode() ? [$quote->getCouponCode()] : []);
        $discountRule = $this->ruleManagement->createFromQuote($quote);
        if ($discountLine = $this->discountLineBuilder->build($discountRule)) {
            $order->setDiscountLines([$discountLine]);
        }

        $items = [];

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $items[] = $this->orderItemBuilder->build([
                'item' => $quoteItem,
                'line_id' => $this->lineIdGenerator->generate($quoteItem)
            ]);
        }

        if ($giftCardItem = $this->giftCardItemBuilder->build(['sales_object' => $quote])) {
            $items[] = $giftCardItem;
        }

        $tax = $quote->getShippingAddress()->getBaseTaxAmount() * 100;
        $order->setVatAmount($tax ?? 0);
        return $order->setItems($items);
    }
}
