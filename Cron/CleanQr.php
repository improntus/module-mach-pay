<?php

namespace Improntus\MachPay\Cron;

use Improntus\MachPay\Model\Config\Data;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

class CleanQr
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var Filesystem
     */
    protected Filesystem $fileSystem;

    /**
     * @var File
     */
    protected Filesystem\Driver\File $file;

    /**
     * @param LoggerInterface $logger
     * @param Filesystem $fileSystem
     * @param File $file
     * @throws FileSystemException
     */
    public function __construct(
        LoggerInterface $logger,
        Filesystem $fileSystem,
        Filesystem\Driver\File $file
    ) {
        $this->logger       = $logger;
        $this->directory    = $fileSystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->filesystem   = $fileSystem;
        $this->file = $file;
    }

    /**
     * Cronjob Description
     *
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('Start Execution of tmp delete cron');
        try {
            $deletedImages = $this->deleteImages();
            $this->logger->info(__("{$deletedImages} have deleted successfully"));
        } catch (\Exception $e) {
            $this->logger->info('Execute Qr delete cron Exception');
            $this->logger->critical($e->getMessage());
        }
        $this->logger->info('End Execution of tmp delete cron');
    }

    /**
     * @return int
     * @throws FileSystemException
     */
    public function deleteImages()
    {
        $deletedImages = 0;
        $absolutePath = $this->directory->getAbsolutePath();
        $mediaPath = $absolutePath . Data::MACHPAY_QR_FOLDER;
        $files = $this->file->readDirectoryRecursively($mediaPath);
        try {
            foreach ($files as $file) {
                if ($file) {
                    if ($this->file->deleteFile($file)) {
                        $deletedImages++;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info('QR file deletion error');
        }
        $this->logger->info('Deleted QR Files : ' . $deletedImages);
        return $deletedImages;
    }
}
