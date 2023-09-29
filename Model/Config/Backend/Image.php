<?php
/**
 * Copyright © Improntus All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Improntus\MachPay\Model\Config\Backend;

class Image extends \Magento\Config\Model\Config\Backend\Image
{
    /**
     * The tail part of directory path for uploading
     *
     */
    public const UPLOAD_DIR = 'machpay'; // Folder save image

    /**
     * Return path to directory for upload file
     *
     * @return string
     * @throw \Magento\Framework\Exception\LocalizedException
     */
    protected function getUploadDir(): string
    {
        return $this->_mediaDirectory->getAbsolutePath($this->_appendScopeInfo(self::UPLOAD_DIR));
    }

    /**
     * Makes a decision about whether to add info about the scope.
     *
     * @return boolean
     */
    protected function addWhetherScopeInfo(): bool
    {
        return true;
    }

    /**
     * Getter for allowed extensions of uploaded files.
     *
     * @return string[]
     */
    protected function getAllowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'svg'];
    }
}
