<?php

namespace Improntus\MachPay\Model\Rest;

use Magento\Framework\HTTP\Client\Curl;
use Improntus\MachPay\Model\Config\Data;

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
     * @param Curl $curl
     * @param Data $helper
     */
    public function __construct(
        Curl $curl,
        Data $helper
    ) {
        $this->helper = $helper;
        $this->curl = $curl;
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
            $this->curl->setHeaders(
                [
                    "Authorization" => "Bearer $token",
                    "Content-Type" => "application/json"
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
