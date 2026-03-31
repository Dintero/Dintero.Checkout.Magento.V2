<?php

namespace Dintero\Checkout\Model\Api\Request\Builder;

use Dintero\Checkout\Api\Data\ShippingMethodInterfaceFactory;
use Dintero\Checkout\Model\Shipping\DeliveryTypeResolver;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\Cart\ShippingMethod;
use Magento\Shipping\Helper\Carrier;

class ShippingOptionBuilder
{
    /**
     * @var ShippingMethodInterfaceFactory $shippingMethodFactory
     */
    private $shippingMethodFactory;

    /**
     * @var ShippingMethodManagementInterface $shippingMethodManagement
     */
    private $shippingMethodManagement;

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
     * Define class dependencies
     *
     * @param ShippingMethodInterfaceFactory $shippingMethodFactory
     * @param ShippingMethodManagementInterface $shippingMethodManagement
     * @param DeliveryTypeResolver $deliveryTypeResolver
     * @param Carrier $carrierHelper
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        ShippingMethodInterfaceFactory $shippingMethodFactory,
        ShippingMethodManagementInterface $shippingMethodManagement,
        DeliveryTypeResolver $deliveryTypeResolver,
        Carrier $carrierHelper,
        CollectionFactory $collectionFactory
    ) {
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->shippingMethodFactory = $shippingMethodFactory;
        $this->deliveryTypeResolver = $deliveryTypeResolver;
        $this->carrierHelper = $carrierHelper;
        $this->countryCollectionFactory = $collectionFactory;
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
        $shippingOption->setAmount($shippingMethod->getPriceInclTax() * 100)
            ->setVat(0)
            ->setVatAmount(($shippingMethod->getPriceInclTax() - $shippingMethod->getPriceExclTax()) * 100)
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
