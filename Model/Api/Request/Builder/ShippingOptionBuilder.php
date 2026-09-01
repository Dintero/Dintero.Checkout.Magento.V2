<?php

namespace Dintero\Checkout\Model\Api\Request\Builder;

use Dintero\Checkout\Api\Data\ShippingMethodInterfaceFactory;
use Dintero\Checkout\Model\Formatter\Amount as AmountFormatter;
use Dintero\Checkout\Model\Shipping\DeliveryTypeResolver;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Quote\Model\Cart\ShippingMethod;
use Magento\Shipping\Helper\Carrier;

class ShippingOptionBuilder
{
    /**
     * @var ShippingMethodInterfaceFactory $shippingMethodFactory
     */
    private $shippingMethodFactory;

    /**
     * @var DeliveryTypeResolver $deliveryTypeResolver
     */
    private $deliveryTypeResolver;

    /**
     * @var Carrier $carrierHelper
     */
    private $carrierHelper;

    /**
     * @var CollectionFactory $countryCollectionFactory
     */
    private $countryCollectionFactory;

    /**
     * @var AmountFormatter $amountFormatter
     */
    private $amountFormatter;

    /**
     * Define class dependencies
     *
     * @param ShippingMethodInterfaceFactory $shippingMethodFactory
     * @param DeliveryTypeResolver $deliveryTypeResolver
     * @param Carrier $carrierHelper
     * @param CollectionFactory $collectionFactory
     * @param AmountFormatter $amountFormatter
     */
    public function __construct(
        ShippingMethodInterfaceFactory $shippingMethodFactory,
        DeliveryTypeResolver $deliveryTypeResolver,
        Carrier $carrierHelper,
        CollectionFactory $collectionFactory,
        AmountFormatter $amountFormatter
    ) {
        $this->shippingMethodFactory = $shippingMethodFactory;
        $this->deliveryTypeResolver = $deliveryTypeResolver;
        $this->carrierHelper = $carrierHelper;
        $this->countryCollectionFactory = $collectionFactory;
        $this->amountFormatter = $amountFormatter;
    }

    /**
     * Retrieve countries
     *
     * @param string $carrierCode
     * @return array|string[]
     */
    protected function getCountries($carrierCode)
    {
        if ($this->carrierHelper->getCarrierConfigValue($carrierCode, 'allowspecific')) {
            return explode(
                ',',
                $this->carrierHelper
                    ->getCarrierConfigValue($carrierCode, 'specificcountry')
            );
        }
        return $this->countryCollectionFactory->create()->getAllIds();
    }

    /**
     * Build shipping option from Shipping method
     *
     * @param ShippingMethod $shippingMethod
     * @param string|int $scope
     * @return \Dintero\Checkout\Api\Data\ShippingMethodInterface
     */
    public function build(ShippingMethod $shippingMethod, $scope = null)
    {
        $shippingOption = $this->shippingMethodFactory->create();
        $shippingOption->setAmount($this->amountFormatter->format($shippingMethod->getPriceInclTax()))
            ->setVat(0)
            ->setVatAmount($this->amountFormatter->format(
                $shippingMethod->getPriceInclTax() - $shippingMethod->getPriceExclTax()
            ))
            ->setOperator($shippingMethod->getCarrierTitle())
            ->setOperatorProductId($shippingMethod->getMethodCode())
            ->setDeliveryMethod($this->deliveryTypeResolver->resolve($shippingMethod->getMethodCode(), $scope))
            ->setTitle($shippingMethod->getMethodTitle())
            ->setDescription($shippingMethod->getMethodTitle())
            ->setLineId(sprintf('%s_%s', $shippingMethod->getCarrierCode(), $shippingMethod->getMethodCode()))
            ->setId(sprintf('%s_%s', $shippingMethod->getCarrierCode(), $shippingMethod->getMethodCode()))
            ->setCountries($this->getCountries($shippingMethod->getCarrierCode()));

        if ($shippingOption->getVatAmount() > 0) {
            $shippingOption->setVat($shippingOption->getVatAmount() / $shippingMethod->getPriceExclTax());
        }

        return $shippingOption;
    }
}
