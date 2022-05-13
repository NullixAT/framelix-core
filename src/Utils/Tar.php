<?php

namespace Framelix\Framelix\Utils;

use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;

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
        $dirsCreated = [];
        $tmpTarDir = __DIR__ . "/../../tmp/tar-extract-" . RandomGenerator::getRandomHtmlId();
        mkdir($tmpTarDir);
        foreach ($files as $relativeName => $fullPath) {
            $fullPath = FileUtils::normalizePath($fullPath);
            if (is_dir($fullPath)) {
                if (!isset($dirsCreated[$fullPath])) {
                    $dirsCreated[$fullPath] = true;
                    mkdir($tmpTarDir . "/" . $relativeName);
                }
            } elseif (file_exists($fullPath)) {
                copy($fullPath, $tmpTarDir . "/" . $relativeName);
            }
        }
        $shell = Shell::prepare('cd {0} && tar cf {1} *', [$tmpTarDir, $tarPath]);
        $shell->execute();
        FileUtils::deleteDirectory($tmpTarDir);
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
        $shell = Shell::prepare('tar xf {*}', [$tarPath, "-C", $outputDirectory]);
        $shell->execute();
        if ($shell->status) {
            throw new \Exception("Error extracting tar file: " . Shell::convertCliOutputToHtml($shell->output, false));
        }
    }
}