<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use ZipArchive;

use function file_exists;
use function is_dir;
use function is_file;
use function scandir;

/**
 * Zip utilities
 */
class Zip
{
    /**
     * Create a zip file with all
     * @param string $zipPath
     * @param string[] $files Key is name in zip, value is full filepath on disk
     * @param int $compressionLevel ZipArchive::CM_*
     */
    public static function createZip(
        string $zipPath,
        array $files,
        int $compressionLevel = ZipArchive::CM_DEFAULT
    ): void {
        $zipArchive = new ZipArchive();
        $openResult = $zipArchive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            throw new Exception("Cannot open ZIP File '$zipPath' ($openResult)", ErrorCode::ZIP_OPEN);
        }
        $index = 0;
        foreach ($files as $relativeName => $fullPath) {
            $fullPath = FileUtils::normalizePath($fullPath);
            if (is_dir($fullPath)) {
                if ($zipArchive->getFromName($relativeName) === false) {
                    $zipArchive->addEmptyDir($relativeName);
                    $zipArchive->setCompressionIndex($index, $compressionLevel);
                    $index++;
                }
            } elseif (file_exists($fullPath)) {
                $zipArchive->addFile($fullPath, $relativeName);
                $zipArchive->setCompressionIndex($index, $compressionLevel);
                $index++;
            }
        }
        $zipArchive->close();
    }

    /**
     * Unzip a zip file into given directory
     * @param string $zipPath
     * @param string $outputDirectory
     * @param bool $skipNotEmptyDirectory If true, it unpacks even if directory is not empty
     * @return void
     */
    public static function unzip(string $zipPath, string $outputDirectory, bool $skipNotEmptyDirectory = false): void
    {
        if (!is_file($zipPath)) {
            throw new Exception("'$zipPath' is no file", ErrorCode::ZIP_UNZIP_NOFILE);
        }
        if (!is_dir($outputDirectory)) {
            throw new Exception("'$outputDirectory' is no directory", ErrorCode::ZIP_UNZIP_NODIRECTORY);
        }
        if (!$skipNotEmptyDirectory) {
            $files = scandir($outputDirectory);
            if (count($files) > 2) {
                throw new Exception("'$outputDirectory' is not empty", ErrorCode::ZIP_UNZIP_NOTEMPTY);
            }
        }
        $zipArchive = new ZipArchive();
        $openResult = $zipArchive->open($zipPath, ZipArchive::RDONLY);
        if ($openResult !== true) {
            throw new Exception("Cannot open ZIP File '$zipPath' ($openResult)", ErrorCode::ZIP_OPEN);
        }
        $zipArchive->extractTo($outputDirectory);
        $zipArchive->close();
    }
}