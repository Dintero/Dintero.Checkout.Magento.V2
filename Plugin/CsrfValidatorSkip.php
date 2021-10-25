<?php
namespace Dintero\Checkout\Plugin;

class CsrfValidatorSkip
{
    /**
     * @param $subject
     * @param \Closure $proceed
     * @param $request
     * @param $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        if ($request->getFullActionName() == 'dintero_payment_response') {
            return; // Skip CSRF check
        }
        $proceed($request, $action); // Proceed Magento 2 core functionalities
    }
}
