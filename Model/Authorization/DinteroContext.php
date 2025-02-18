<?php

namespace Dintero\Checkout\Model\Authorization;

use Dintero\Checkout\Api\EmbeddedCallbackInterface;
use Dintero\Checkout\Api\ExpressCallbackInterface;
use Dintero\Checkout\Api\ShippingCallbackInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Webapi\Model\Config\Converter;
use Magento\Webapi\Model\ConfigInterface;

class DinteroContext implements UserContextInterface, ResetAfterRequestInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var int
     */
    protected $userType;

    /**
     * @var bool
     */
    protected $requestProcessed = false;

    /**
     * @var string[]
     */
    protected $callbackHandlers = [
        EmbeddedCallbackInterface::class,
        ExpressCallbackInterface::class,
        ShippingCallbackInterface::class,
    ];

    /**
     * Class dependencies
     *
     * @param ConfigInterface $config
     * @param Request $request
     */
    public function __construct(
        ConfigInterface $config,
        Request $request
    ) {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * Process request
     * @todo add Dintero signature processing
     *
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function processRequest()
    {
        if ($this->requestProcessed) {
            return;
        }

        $this->requestProcessed = true;

        if (stripos($this->request->getPathInfo(), 'dintero') === false) {
            return;
        }

        $requestHttpMethod = $this->request->getHttpMethod();
        $servicesRoutes = $this->config->getServices()[Converter::KEY_ROUTES];

        if (!isset($servicesRoutes[$this->request->getPathInfo()][$requestHttpMethod])) {
            return;
        }

        $methodInfo = $servicesRoutes[$this->request->getPathInfo()][$requestHttpMethod];
        $serviceClass = $methodInfo[Converter::KEY_SERVICE][Converter::KEY_SERVICE_CLASS];

        if (in_array($serviceClass, $this->callbackHandlers)) {
            $this->userId = 'dintero';
            $this->userType = self::USER_TYPE_INTEGRATION;
        }
    }

    /**
     * Retrieve user id
     *
     * @return string
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getUserId()
    {
        $this->processRequest();
        return $this->userId;
    }

    /**
     * Retrieve user type
     *
     * @return int
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getUserType()
    {
        $this->processRequest();
        return $this->userType;
    }

    /**
     * Reset state
     *
     * @return void
     */
    public function _resetState(): void
    {
        $this->requestProcessed = false;
        $this->userId = null;
        $this->userType = null;
    }
}
