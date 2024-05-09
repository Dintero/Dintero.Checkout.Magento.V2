<?php

namespace Dintero\Checkout\Model;

use Dintero\Checkout\Api\Data\Order\ItemInterfaceFactory;
use Dintero\Checkout\Api\Data\OrderInterfaceFactory;
use Dintero\Checkout\Api\Data\Shipping\RequestInterface;
use Dintero\Checkout\Api\Data\Shipping\RequestInterfaceFactory;
use Dintero\Checkout\Api\Data\Shipping\ResponseInterfaceFactory;
use Dintero\Checkout\Api\Data\ShippingMethodInterface;
use Dintero\Checkout\Api\Data\ShippingMethodInterfaceFactory;
use Dintero\Checkout\Helper\Config;
use Dintero\Checkout\Model\Api\Request\LineIdGenerator;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Shipping\Helper\Carrier;
use Psr\Log\LoggerInterface;

/**
 * Class ShippingCallback
 *
 * @package Dintero\Checkout\Model
 */
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
     * @var ShippingMethodInterfaceFactory
     */
    protected $shippingOptionFactory;

    /**
     * @var Carrier $carrierHelper
     */
    protected $carrierHelper;

    /**
     * @var CollectionFactory
     */
    protected $countryCollectionFactory;

    /**
     * @var OrderInterfaceFactory $orderFactory
     */
    protected $orderFactory;

    /**
     * @var ItemInterfaceFactory $orderItemFactory
     */
    protected $orderItemFactory;

    /**
     * @var Config|ConfigHelper
     */
    protected $configHelper;

    /**
     * @var LineIdGenerator $lineIdGenerator
     */
    protected $lineIdGenerator;

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
     * @param ShippingMethodInterfaceFactory $shippingOptionFactory
     * @param Carrier $carrierHelper
     * @param CollectionFactory $countryCollectionFactory
     * @param OrderInterfaceFactory $orderFactory
     * @param ItemInterfaceFactory $orderItemFactory
     * @param ConfigHelper $configHelper
     * @param LineIdGenerator $lineIdGenerator
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
        ShippingMethodInterfaceFactory $shippingOptionFactory,
        Carrier $carrierHelper,
        CollectionFactory $countryCollectionFactory,
        OrderInterfaceFactory $orderFactory,
        ItemInterfaceFactory $orderItemFactory,
        Config $configHelper,
        LineIdGenerator $lineIdGenerator
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
        $this->shippingOptionFactory = $shippingOptionFactory;
        $this->carrierHelper = $carrierHelper;
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->orderFactory = $orderFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->configHelper = $configHelper;
        $this->lineIdGenerator = $lineIdGenerator;
    }

    /**
     * Resolve Delivery type by shipping method
     *
     * @param string $shippingMethod
     * @param string $scopeCode
     * @return string
     */
    protected function resolveDeliveryType($shippingMethod, $scopeCode = null)
    {
        if(in_array($shippingMethod, $this->configHelper->getPickupMethods($scopeCode))) {
            return ShippingMethodInterface::DELIVERY_METHOD_PICKUP;
        }

        if (in_array($shippingMethod, $this->configHelper->getUnspecifiedMethods($scopeCode))) {
            return ShippingMethodInterface::DELIVERY_METHOD_UNSPECIFIED;
        }

        return ShippingMethodInterface::DELIVERY_METHOD_DELIVERY;
    }

    /**
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
            throw new \Exception(__('Quote is not valid'));
        }

        $quote->setCouponCode(current($request->getDiscountCodes()) ?? null);
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
            /** @var ShippingMethodInterface $shippingOption */
            $shippingOption = $this->shippingOptionFactory->create();

            $shippingOption->setAmount($shippingMethod->getPriceInclTax() * 100)
                ->setVat(0)
                ->setVatAmount(($shippingMethod->getPriceInclTax() - $shippingMethod->getPriceExclTax()) * 100)
                ->setOperator($shippingMethod->getCarrierTitle())
                ->setOperatorProductId($shippingMethod->getMethodCode())
                ->setDeliveryMethod($this->resolveDeliveryType($shippingMethod->getMethodCode(), $quote->getStoreId()))
                ->setTitle($shippingMethod->getMethodTitle())
                ->setDescription($shippingMethod->getMethodTitle())
                ->setLineId(sprintf('%s_%s', $shippingMethod->getCarrierCode(), $shippingMethod->getMethodCode()))
                ->setId(sprintf('%s_%s', $shippingMethod->getCarrierCode(), $shippingMethod->getMethodCode()))
                ->setCountries($this->getCountries($shippingMethod->getCarrierCode()));

            if ($shippingOption->getVatAmount() > 0) {
                $shippingOption->setVat($shippingOption->getVatAmount() / $shippingMethod->getPriceExclTax());
            }

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
     * @param $carrierCode
     * @return false|string[]
     */
    protected function getCountries($carrierCode)
    {
        if ($this->carrierHelper->getCarrierConfigValue($carrierCode, 'sallowspecific')) {
            return explode(
                ',',
                $this->carrierHelper
                    ->getCarrierConfigValue($carrierCode, 'specificcountry')
            );
        }
        return $this->countryCollectionFactory->create()->getAllIds();
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Dintero\Checkout\Api\Data\OrderInterface
     */
    protected function prepareOrder(\Magento\Quote\Model\Quote $quote)
    {
        /** @var \Dintero\Checkout\Api\Data\OrderInterface $order */
        $order = $this->orderFactory->create();
        $baseShippingAmount = $quote->getShippingAddress()->getBaseShippingAmount() * 100;
        $baseShippingTaxAmount = $quote->getShippingAddress()->getBaseShippingTaxAmount() * 100;
        $baseShippingTotalAmount = $baseShippingAmount + $baseShippingTaxAmount;

        // shipping amount should be subtracted, otherwise correction item on checkout
        // will be added as shipping amount will be added on top
        $orderTotal = $quote->getBaseGrandTotal() * 100;
        $orderTotal -= $baseShippingTotalAmount;

        $order->setAmount($orderTotal)
            ->setCurrency($quote->getBaseCurrencyCode());
        $order->setDiscountCodes($quote->getCouponCode() ? [$quote->getCouponCode()] : []);

        $items = [];

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            /** @var \Dintero\Checkout\Api\Data\Order\ItemInterface $orderItem */
            $orderItem = $this->orderItemFactory->create();
            $orderItem->setAmount(($quoteItem->getBaseRowTotalInclTax() - $quoteItem->getBaseDiscountAmount()) * 100)
                ->setId($quoteItem->getSku())
                ->setLineId($this->lineIdGenerator->generate($quoteItem))
                ->setDescription(sprintf('%s (%s)', $quoteItem->getName(), $quoteItem->getSku()))
                ->setQuantity($quoteItem->getQty() * 1)
                ->setVat($quoteItem->getTaxPercent())
                ->setVatAmount($quoteItem->getBaseTaxAmount() * 100);
            array_push($items, $orderItem);
        }
        $tax = $quote->getShippingAddress()->getBaseTaxAmount() * 100;
        $order->setVatAmount($tax ?? 0);
        return $order->setItems($items);
    }
}
