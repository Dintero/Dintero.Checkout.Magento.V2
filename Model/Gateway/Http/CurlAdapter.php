<?php

namespace Dintero\Checkout\Model\Gateway\Http;

class CurlAdapter extends \Magento\Framework\HTTP\Adapter\Curl
{

    /**
     * Fix headers
     *
     * @param array $headers
     * @return array
     */
    private function fixHeaders($headers)
    {
        $correctHeaders = [];
        foreach ($headers as $key => $value) {
            $correctHeaders[] = implode(':', [$key, $value]);
        }
        return $correctHeaders;
    }

    /**
     * Overriding original logic because of the bug: https://github.com/magento/magento2/issues/37641
     *
     * @param string $method
     * @param string $url
     * @param string $http_ver
     * @param array $headers
     * @param string $body
     * @return string
     */
    public function write($method, $url, $http_ver = '1.1', $headers = [], $body = '')
    {
        $body = parent::write($method, $url, $http_ver, $headers, $body);

        if (is_array($headers)) {
            curl_setopt($this->_getResource(), CURLOPT_HTTPHEADER, $this->fixHeaders($headers));
        }

        return $body;
    }
}
