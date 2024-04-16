<?php

namespace Dintero\Checkout\Api;

/**
 * Interface SessionManagementInterface
 *
 * @package Dintero\Checkout\Api
 */
interface SessionManagementInterface
{
    /**
     * @return \Dintero\Checkout\Api\Data\SessionInterface
     */
    public function getSession();

    /**
     * @return \Dintero\Checkout\Api\Data\SessionInterface
     */
    public function updateSession();

    /**
     * @param string $sessionId
     * @return boolean
     */
    public function validateSession($sessionId);
}
