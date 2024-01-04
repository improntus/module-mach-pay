<?php

namespace Improntus\MachPay\Model\Config;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Encryption\EncryptorInterface;
use Improntus\MachPay\Logger\Logger;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data - Helper class for configurations
 * @package Improntus\MachPay\Model\Config
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** class consts */
    private const CONFIG_ROOT = 'payment/machpay/';
    public const ACTIVE  = 'active';
    public const TITLE = 'title';
    public const ORDER_STATUS = 'order_status';
    public const APPROVED_STATUS = 'status_approved';
    public const CANCELED_STATUS = 'status_canceled';
    public const REFUND_AVAILABLE = 'refund_available';
    public const DEBUG = 'debug';
    public const COMPANY_NAME = 'company_name';
    public const LOGO = 'logo';
    public const API_ENDPOINT = 'endpoint';
    public const API_TOKEN = 'token';
    public const CANCEL_ORDERS_ACTIVE = 'cancel_orders/active';
    public const CANCEL_ORDERS_HOURS = 'cancel_hours';
    public const UPLOAD_DIR = 'machpay/';
    public const MERCHANT_PAYMENTS = 'payments/';
    public const USER_AUTHENTICATED = 1;
    public const INCOMPLETE_CREDENTIALS = 0;
    public const MACHPAY_QR_FOLDER = 'machpay/qr/';

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
     * @var Filesystem
     */
    private FileSystem $fileSystem;

    /**
     * @var File
     */
    private File $file;

    /**
     * Helper Constructor
     *
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param FileSystem $fileSystem
     * @param File $file
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        Logger $logger,
        StoreManagerInterface $storeManager,
        Filesystem $fileSystem,
        Filesystem\Driver\File $file
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->fileSystem = $fileSystem;
        $this->file = $file;
    }

    /**
     * Get Config value of field
     *
     * @param string $value
     * @param null|int $storeId
     * @return mixed|string
     */
    public function getConfigData(string $value, int $storeId = null)
    {
        $path = $this::CONFIG_ROOT . $value;
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? '';
    }

    /**
     * Retrieve if payment method is enabled
     *
     * @return boolean
     */
    public function isEnabled(): bool
    {
        return (bool)$this->getConfigData(self::ACTIVE);
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getConfigData(self::TITLE);
    }

    /**
     * Retrieve payment new order status
     *
     * @return string
     */
    public function getNewOrderStatus()
    {
        return $this->getConfigData(self::ORDER_STATUS);
    }

    /**
     * Retrieve payment approved status
     *
     * @return string
     */
    public function getApprovedStatus()
    {
        return $this->getConfigData(self::APPROVED_STATUS);
    }

    /**
     * Retrieve payment canceled status
     *
     * @return string
     */
    public function getCanceledStatus()
    {
        return $this->getConfigData(self::CANCELED_STATUS);
    }

    /**
     * Retrieve API endpoint
     *
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return $this->getConfigData(self::API_ENDPOINT);
    }

    /**
     * Retrieve API authorization token
     *
     * @return string
     */
    public function getApiToken(): string
    {
        return $this->getConfigData(self::API_TOKEN) ?? '';
    }

    /**
     * Validate if debug mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugEnabled(int $storeId = null)
    {
        return (bool)$this->getConfigData($this::DEBUG, $storeId);
    }

    /**
     * Return logo img path
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getLogo()
    {
        if ($filePath = $this->getConfigData(self::LOGO)) {
            return $this->storeManager->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . self::UPLOAD_DIR . $filePath;
        }

        return $filePath;
    }

    /**
     * @return mixed|string
     */
    public function getCompanyName()
    {
        return $this->getConfigData(self::COMPANY_NAME);
    }

    /**
     * Retrieve if cron is enabled
     *
     * @return bool
     */
    public function isCronEnabled()
    {
        return (bool) $this->getConfigData(self::CANCEL_ORDERS_ACTIVE);
    }

    /**
     * Retrieve if refund is available
     *
     * @return bool
     */
    public function isRefundAvailable()
    {
        return (bool) $this->getConfigData(self::REFUND_AVAILABLE);
    }

    /**
     * Get time for search pending orders
     *
     * @return string|null
     */
    public function getCancelHours()
    {
        return $this->getConfigData(self::CANCEL_ORDERS_HOURS);
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
     * Get Callback Url
     *
     * @param string $token
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCallBackUrl(string $token = null)
    {
        if ($token) {
            return $this->_getUrl('machpay/order/response', ['token' => $token]);
        }
        return $this->_getUrl('machpay/order/response');
    }

    /**
     * Set Log
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    public function log(string $message, string $type = 'debug')
    {
        if ($this->isDebugEnabled()) {
            $this->logger->setName('debug');
            if ($type !== 'debug') {
                $this->logger->info($message);
            } else {
                $this->logger->debug($message);
            }
        }
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
     * Validate Credentials
     *
     * @return int
     */
    public function validateCredentials()
    {
        $result = $this::INCOMPLETE_CREDENTIALS;
        if ($this->getConfigData(self::API_TOKEN) && $this->getConfigData(self::API_ENDPOINT)) {
            $result = $this::USER_AUTHENTICATED;
        }
        return $result;
    }

    /**
     * Detect if is a device or not
     *
     * @return bool
     */
    public function isMobile()
    {
        if (preg_match(
            "/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i",
            $this->_httpHeader->getHttpUserAgent())
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get Image Base 64 of string with data
     *
     * @param string $imgBase64
     * @return string
     */
    public function getImageBase64(string $imgBase64)
    {
        $stringB64 = explode(',', $imgBase64);
        return trim(end($stringB64));
    }

    /**
     * Upload image QR to folder machpay/qr
     *
     * @param $image
     * @param string $incrementId
     * @return string
     * @throws \Exception
     */
    public function uploadQrImage($image, string $incrementId)
    {
        $nameFile = "{$incrementId}.png";
        $path = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath(self::MACHPAY_QR_FOLDER);
        $filePath = $path . $nameFile;
        try {
            if (!$this->file->isDirectory($path)) {
                $this->file->createDirectory($path);
            }
            $file = $this->file->fileOpen($filePath, "w");
            $this->file->fileWrite($file, $image);
            $this->file->fileClose($file);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        return $filePath;
    }
}
