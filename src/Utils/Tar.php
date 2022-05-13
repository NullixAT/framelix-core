<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use PharData;

use function file_exists;
use function is_dir;
use function is_file;
use function scandir;

/**
 * Tar utilities
 */
class Tar
{
    /**
     * Create a tarball file
     * @param string $tarPath
     * @param string[] $files Key is path in tar, value is full filepath on disk
     */
    public static function createTar(
        string $tarPath,
        array $files
    ): void {
        $archive = new PharData($tarPath);
        $dirsCreated = [];
        foreach ($files as $relativeName => $fullPath) {
            $fullPath = FileUtils::normalizePath($fullPath);
            if (is_dir($fullPath)) {
                if (!isset($dirsCreated[$fullPath])) {
                    $dirsCreated[$fullPath] = true;
                    $archive->addEmptyDir($relativeName);
                }
            } elseif (file_exists($fullPath)) {
                $archive->addFile($fullPath, $relativeName);
            }
        }
    }

    /**
     * Extract a tarball's files into given directory
     * @param string $tarPath
     * @param string $outputDirectory
     * @param bool $skipNotEmptyDirectory If true, it unpacks even if directory is not empty
     * @return void
     */
    public static function extractTo(
        string $tarPath,
        string $outputDirectory,
        bool $skipNotEmptyDirectory = false
    ): void {
        if (!is_file($tarPath)) {
            throw new Exception("'$tarPath' is no file", ErrorCode::TAR_EXTRACT_NOFILE);
        }
        if (!is_dir($outputDirectory)) {
            throw new Exception("'$outputDirectory' is no directory", ErrorCode::TAR_EXTRACT_NODIRECTORY);
        }
        if (!$skipNotEmptyDirectory) {
            $files = scandir($outputDirectory);
            if (count($files) > 2) {
                throw new Exception("'$outputDirectory' is not empty", ErrorCode::TAR_EXTRACT_NOTEMPTY);
            }
        }
        Shell::prepare('tar xf {*}', [$tarPath, $outputDirectory]);
    }
}