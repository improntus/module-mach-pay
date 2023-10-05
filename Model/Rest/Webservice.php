<?php

namespace Improntus\MachPay\Model\Rest;

use Magento\Framework\HTTP\Client\Curl;
use Improntus\MachPay\Model\Config\Data;

/**
 * Class Webservice - Brief description of class objective
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

    public function __construct(
        Curl $curl,
        Data $helper
    )
    {
        $this->helper = $helper;
        $this->curl = $curl;
    }

    /**
     * Do request to endpoint
     * @param $endpoint
     * @param $secret
     * @param $data
     * @param $method
     * @param $storeId
     * @param $options
     * @return mixed|string
     */
    public function doRequest($endpoint, $secret,  $data=null, $method=null, $storeId = null, $options=null)
    {
        if (null === $method) {
            $method = "POST";
        }
        $baseUrl = $this->helper->getApiEndpoint();

        $url = $baseUrl . $endpoint;

        $basic = base64_encode($secret);

        try
        {
            if (!is_null($options)) {
                $this->curl->setOptions($options);
            }
            $this->curl->setHeaders(
                [
                    "Authorization" => $basic,
                    "Content-Type" => "application/json"
                ]
            );
            $data = json_encode($data);
            switch ($method)
            {
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
