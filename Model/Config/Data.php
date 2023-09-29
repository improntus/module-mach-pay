<?php

namespace Improntus\MachPay\Model\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Improntus\MachPay\Logger\Logger;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data - Helper class for configurations
 * @package Improntus\MachPay\Model\Config
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** class consts */
    protected const INCOMPLETE_CREDENTIALS = 0;
    protected const USER_AUTHENTICATED = 1;
    protected const LOGGER_NAME = 'machpay';
    protected const UPLOAD_DIR = 'machpay/';
    protected const STATUS_OK = [200, 201];
    protected const STATUS_ERROR = 'error';

    /** Configuration path for Pronto Paga payment section */
    protected const XML_PATH_IMPRONTUS_MACHPAY_ACTIVE  = 'payment/machpay/active';
    protected const XML_PATH_IMPRONTUS_MACHPAY_TITLE = 'payment/machpay/title';
    protected const XML_PATH_IMPRONTUS_MACHPAY_ORDER_STATUS = 'payment/machpay/order_status';
    protected const XML_PATH_IMPRONTUS_MACHPAY_APPROVED_STATUS = 'payment/machpay/status_approved';
    protected const XML_PATH_IMPRONTUS_MACHPAY_CANCELED_STATUS = 'payment/machpay/status_canceled';
    protected const XML_PATH_IMPRONTUS_MACHPAY_REFUND_AVAILABLE = 'payment/machpay/refund_available';
    protected const XML_PATH_IMPRONTUS_MACHPAY_API_ENDPOINT = 'payment/machpay/credentials/endpoint';
    protected const XML_PATH_IMPRONTUS_MACHPAY_API_TOKEN = 'payment/machpay/credentials/token';
    protected const XML_PATH_IMPRONTUS_MACHPAY_LOGO = 'payment/machpay/logo';
    protected const XML_PATH_IMPRONTUS_MACHPAY_CANCEL_ORDERS_ACTIVE = 'payment/machpay/cancel_orders/active';
    protected const XML_PATH_IMPRONTUS_MACHPAY_CANCEL_ORDERS_TINTERVAL = 'payment/machpay/cancel_orders/timeinterval';

    /** Get country path */
    protected const COUNTRY_CODE_PATH = 'general/country/default';

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Helper Constructor
     *
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * Get Config value of field
     *
     * @param string $field
     * @param int|null $storeId
     * @return string|null
     */
    public function getConfigValue(string $field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve if payment method is enabled
     *
     * @return boolean
     */
    public function isEnabled(): bool
    {
        return (bool)$this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_ACTIVE);
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_TITLE);
    }

    /**
     * Retrieve payment new order status
     *
     * @return string
     */
    public function getNewOrderStatus()
    {
        return $this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_ORDER_STATUS);
    }

    /**
     * Retrieve payment approved status
     *
     * @return string
     */
    public function getApprovedStatus()
    {
        return $this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_APPROVED_STATUS);
    }

    /**
     * Retrieve payment canceled status
     *
     * @return string
     */
    public function getCanceledStatus()
    {
        return $this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_CANCELED_STATUS);
    }

    /**
     * Retrieve API endpoint
     *
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return $this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_API_ENDPOINT);
    }

    /**
     * Retrieve API authorization token
     *
     * @return string
     */
    public function getApiToken(): string
    {
        return $this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_API_TOKEN) ?? '';
    }

    /**
     * Return logo img path
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getLogo()
    {
        if ($filePath = $this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_LOGO)) {
            return $this->storeManager->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . self::UPLOAD_DIR  .  $filePath;
        }

        return $filePath;
    }

    /**
     * Retrieve if cron is enabled
     *
     * @return bool
     */
    public function isCronEnabled()
    {
        return (bool) $this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_CANCEL_ORDERS_ACTIVE);
    }

    /**
     * Retrieve if refund is available
     *
     * @return bool
     */
    public function isRefundAvailable()
    {
        return (bool) $this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_REFUND_AVAILABLE);
    }

    /**
     * Retrieve time interval for search pending orders
     *
     * @return string|null
     */
    public function getTimeInterval()
    {
        return $this->getConfigValue(self::XML_PATH_IMPRONTUS_MACHPAY_CANCEL_ORDERS_TINTERVAL);
    }

    /**
     * Validate credentials
     *
     * @return integer
     */
    public function validateCredentials(): int
    {
        if ($this->getApiEndpoint() && $this->getApiToken()) {
            return self::USER_AUTHENTICATED;
        }
        return self::INCOMPLETE_CREDENTIALS;
    }

    /**
     * Return the url for create order
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRedirectUrl(): string
    {
        return $this->_getUrl('machpay/order/create', ['_secure' => 'true']);
    }

    /**
     * Return the callback url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCallBackUrl(): string
    {
        return $this->_getUrl(null, [
            '_path' => 'enquiry',
            '_secure' => true,
            '_direct' => 'rest/V1/machpay/callback'
        ]);
    }

    /**
     * Get response url
     *
     * @param array|string $params
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getResponseUrl($params = []): string
    {
        return $this->_getUrl('machpay/order/response', $params);
    }

    /**
     * Get firm for secret key
     *
     * @param array $data
     * @return string
     * @see https://sandbox.insospa.com/files/documentation/index.php?lang=es#sign-with-your-secretkey
     */
    public function firmSecretKey(array $data): string
    {
        $keys = array_keys($data);
        sort($keys);
        $toSign = '';
        foreach ($keys as $key) {
            $toSign .= $key . $data[$key];
        }

        return hash_hmac('sha256', $toSign, $this->getSecretKey());
    }

    /**
     * Set log
     *
     * @param array $data
     * @return void
     */
    public function log(array $data): void
    {
        $this->logger->setName(self::LOGGER_NAME);
        $data['type'] !== 'debug'
            ? $this->logger->{$data['type']}($data['message'], ['method_context' => $data['method']])
            : $this->logger->debug($data['message'], ['method_context' => $data['method']]);
    }

    /**
     * Encrypt params
     *
     * @param string $params
     * @param bool $base64
     * @return string
     */
    public function encrypt(string $params, bool $base64 = false): string
    {
        if ($base64) {
            return base64_encode($this->encryptor->encrypt($params));
        }
        return $this->encryptor->encrypt($params);
    }

    /**
     * Decrypt params
     *
     * @param string $params
     * @param bool $base64
     * @return string
     */
    public function decrypt(string $params, bool $base64 = false): string
    {
        if ($base64) {
            return $this->encryptor->decrypt(base64_decode($params));
        }
        return $this->encryptor->decrypt($params);
    }

    /**
     * Get Country code by store scope
     *
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->getConfigValue(
            self::COUNTRY_CODE_PATH
        );
    }

    /**
     * Retrieve store currency code
     *
     * @param string|int $storeId
     * @return string
     * @throws LocalizedException
     */
    public function getCurrency($storeId = null): string
    {
        return $this->storeManager->getStore($storeId)->getCurrentCurrency()->getCode();
    }

    /**
     * Validate request callback
     *
     * @param array $response
     * @return bool
     */
    public function validateSing(array $response): bool
    {
        $sign = $response['sign'];
        unset($response['sign']);
        return hash_equals($sign, $this->firmSecretKey($response));
    }
}
