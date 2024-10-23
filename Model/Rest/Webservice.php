<?php

namespace Improntus\MachPay\Model\Rest;

use Magento\Framework\HTTP\Client\Curl;
use Improntus\MachPay\Model\Config\Data;
use Magento\Framework\HTTP\Header;

/**
 * Class Webservice - Create request in webservice
 * @package Improntus\MachPay\Model\Rest
 */
class Webservice
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Header
     */
    private $header;

    /**
     * @param Curl $curl
     * @param Data $helper
     * @param Header $header
     */
    public function __construct(
        Curl $curl,
        Data $helper,
        Header $header
    ) {
        $this->helper = $helper;
        $this->curl = $curl;
        $this->header = $header;
    }

    /**
     * Do request to endpoint
     *
     * @param string $endpoint
     * @param array|null $data
     * @param string|null $method
     * @param array|null $options
     * @return mixed|string
     */
    public function doRequest(
        string $endpoint,
        array $data = null,
        string $method = null,
        array $options = null
    ) {
        if (null === $method) {
            $method = "POST";
        }
        $baseUrl = $this->helper->getApiEndpoint();

        $token = $this->helper->getApiToken();
        $url = $baseUrl . $endpoint;

        try {
            if ($options !== null) {
                $this->curl->setOptions($options);
            }

            $userAgent = $this->header->getHttpUserAgent();

            if(!$userAgent){
                $userAgent = 'ImprontusMach/1.0.4 (Magento 2)';
            }

            $this->curl->setHeaders(
                [
                    "Authorization" => "Bearer $token",
                    "Content-Type" => "application/json",
                    "User-Agent" => $userAgent
                ]
            );

            if ($data === null) {
                $data = new \stdClass();
            }

            $data = json_encode($data);
            switch ($method) {
                case "POST":
                    $this->curl->post($url, $data);
                    break;
                case "GET":
                    $this->curl->get($url);
            }

            return json_decode($this->curl->getBody(), true);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            return $e->getMessage();
        }
    }
}
