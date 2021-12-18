<?php

namespace Framelix\Framelix\Utils;

use Exception;
use ZipArchive;

use function file_exists;
use function is_dir;
use function is_file;
use function scandir;
use function str_contains;
use function strpos;
use function substr;

/**
 * Zip utilities
 */
class Zip
{
    /**
     * Create a zip file with all
     * @param string $zipPath
     * @param string[] $files Key is name in zip, value is full filepath on disk
     */
    public static function createZip(string $zipPath, array $files): void
    {
        $zipArchive = new ZipArchive();
        $openResult = $zipArchive->open($zipPath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        if ($openResult !== true) {
            throw new Exception("Cannot open ZIP File '$zipPath' ($openResult)");
        }
        foreach ($files as $relativeName => $fullPath) {
            $fullPath = FileUtils::normalizePath($fullPath);
            if (is_dir($fullPath)) {
                if ($zipArchive->getFromName($relativeName) === false) {
                    $zipArchive->addEmptyDir($relativeName);
                }
            } elseif (file_exists($fullPath)) {
                $zipArchive->addFile($fullPath, $relativeName);
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
            throw new Exception("'$zipPath' is no file");
        }
        if (!is_dir($outputDirectory)) {
            throw new Exception("'$outputDirectory' is no directory");
        }
        if (!$skipNotEmptyDirectory) {
            $files = scandir($outputDirectory);
            if (count($files) > 2) {
                throw new Exception("'$outputDirectory' is not empty");
            }
        }
        $zipArchive = new ZipArchive();
        $openResult = $zipArchive->open($zipPath, ZipArchive::RDONLY);
        if ($zipArchive->open($zipPath, ZipArchive::RDONLY) !== true) {
            throw new Exception("Cannot open ZIP File '$zipPath' ($openResult)");
        }
        $zipArchive->extractTo($outputDirectory);
        $zipArchive->close();
    }
}