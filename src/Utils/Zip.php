<?php

namespace Framelix\Framelix\Utils;

use ZipArchive;

use function dirname;
use function file_exists;
use function is_dir;
use function str_contains;
use function strlen;
use function strpos;
use function substr;
use function var_dump;

/**
 * Zip utilities
 */
class Zip
{
    /**
     * Create a zip file with all
     * @param string $zipPath
     * @param string[] $files Key is name in zip, value is full filepath on disk
     * @return bool
     */
    public static function createZip(string $zipPath, array $files): bool
    {
        $zip = new ZipArchive();
        if (!$zip->open($zipPath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
            return false;
        }
        foreach ($files as $relativeName => $fullPath) {
            $fullPath = FileUtils::normalizePath($fullPath);
            if (is_dir($fullPath)) {
                if ($zip->getFromName($relativeName) === false) {
                    $zip->addEmptyDir($relativeName);
                }
            } elseif (file_exists($fullPath)) {
                if (str_contains($relativeName, "/")) {
                    $relativeDirName = substr($relativeName, 0, strpos($relativeName, "/"));
                    if ($zip->getFromName($relativeDirName) === false) {
                        $zip->addEmptyDir($relativeDirName);
                    }
                }
                $zip->addFile($fullPath, $relativeName);
            }
        }
        $zip->close();
        return true;
    }
}