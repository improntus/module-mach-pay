<?php

namespace Improntus\MachPay\Model;

use Improntus\MachPay\Model\Config\Data as MachPayHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ConfigProvider - Brief description of class objective
 * @package Improntus\MachPay\Model
 */
class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    public const CODE = 'machpay';
    public const BANNER = 'Improntus_MachPay::images/machpay.jpeg';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AssetRepository
     *
     */
    private $assetRepository;

    /**
     * @var MachPayHelper
     */
    private $machPayHelper;

    /**
     * Constructor
     *
     * @param AssetRepository $assetRepository
     * @param MachPayHelper $machPayHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AssetRepository $assetRepository,
        MachPayHelper $machPayHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->assetRepository = $assetRepository;
        $this->machPayHelper = $machPayHelper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'active' => $this->machPayHelper->isEnabled() && $this->machPayHelper->validateCredentials(),
                    'redirect_url' => $this->machPayHelper->getRedirectUrl(),
                    'title' => $this->machPayHelper->getTitle(),
                    'logo' => $this->machPayHelper->getLogo() ?? $this->assetRepository->getUrl(self::BANNER),
                    'code' =>  self::CODE
                ]
            ],
        ];
    }
}
