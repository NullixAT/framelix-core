<?php

namespace Framelix\Framelix\Html;

use Framelix\Framelix\Config;
use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Shell;

use function array_combine;
use function array_key_exists;
use function array_values;
use function base64_encode;
use function basename;
use function file_exists;
use function filemtime;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function realpath;
use function unlink;

/**
 * Javascript and SCSS compiler
 */
class Compiler
{
    /**
     * Internal cache
     * Not modify it, it is public just for unit tests
     * @var array
     */
    public static array $cache = [];

    /**
     * Get dist url for given parameters
     * @param string $module
     * @param string $type Could be js or scss
     * @param string $groupId
     * @return string
     */
    public static function getDistFilePath(string $module, string $type, string $groupId): string
    {
        $extension = $type === 'js' ? 'js' : 'css';
        return FileUtils::getModuleRootPath($module) . "/public/dist/$extension/$groupId.$extension";
    }

    /**
     * Get dist url for given parameters
     * @param string $module
     * @param string $type Could be js or scss
     * @param string $groupId
     * @return Url
     */
    public static function getDistUrl(string $module, string $type, string $groupId): Url
    {
        $file = self::getDistFilePath($module, $type, $groupId);
        if (!file_exists($file)) {
            throw new Exception("DistFile $module->$type->$groupId not exist", ErrorCode::COMPILER_DISTFILE_NOTEXIST);
        }
        return Url::getUrlToFile($file);
    }

    /**
     * Get dist metadata for module
     * @param string $module
     * @return array|null
     */
    public static function getDistMetadata(string $module): ?array
    {
        if (array_key_exists($module, self::$cache)) {
            return self::$cache[$module];
        }
        self::$cache[$module] = null;
        $moduleRoot = FileUtils::getModuleRootPath($module);
        $metaFilePath = "$moduleRoot/public/dist/_meta.json";
        if (file_exists($metaFilePath)) {
            self::$cache[$module] = JsonUtils::readFromFile($metaFilePath);
        }
        return self::$cache[$module];
    }

    /**
     * Checks if the required nodejs compiler scripts are installed
     * @return bool
     */
    public static function isCompilerAvailable(): bool
    {
        return is_dir(__DIR__ . "/../../node_modules/@babel");
    }

    /**
     * Compile js and scss files for given module
     * @param string $module
     * @return string[]|null Return array of compiled dist file paths
     */
    public static function compile(string $module): ?array
    {
        // cannot compile in production
        if (!Config::isDevMode()) {
            return null;
        }
        // already compiled, skip
        if (isset(self::$cache['compiled-' . $module])) {
            return null;
        }
        self::$cache['compiled-' . $module] = true;
        // cannot compile when required node module is missing
        if (!self::isCompilerAvailable()) {
            throw new Exception(
                "Missing required NodeJs modules for compiler. Please run `npm install` in " . realpath(
                    __DIR__ . "/../.."
                ),
                ErrorCode::COMPILER_BABEL_MISSING
            );
        }
        $compilerData = Config::get("compiler[$module]");
        if (!$compilerData) {
            return null;
        }
        // meta file will store previous compiler data
        // if anything changes, then we need to force an update
        $moduleRoot = FileUtils::getModuleRootPath($module);
        $existingDistFiles = FileUtils::getFiles("$moduleRoot/public/dist", "~\.(js|css)$~", true);
        $existingDistFiles = array_combine($existingDistFiles, $existingDistFiles);
        $metaFilePath = "$moduleRoot/public/dist/_meta.json";
        $forceUpdate = false;
        if (!file_exists($metaFilePath) || JsonUtils::readFromFile($metaFilePath) !== $compilerData) {
            $forceUpdate = true;
        }
        $returnDistFiles = [];
        foreach ($compilerData as $type => $groups) {
            foreach ($groups as $groupId => $groupData) {
                $files = [];
                $distFilePath = FileUtils::normalizePath(self::getDistFilePath($module, $type, $groupId));
                foreach ($groupData['files'] as $row) {
                    if ($row['type'] === 'file') {
                        if (is_array($row['path'])) {
                            foreach ($row['path'] as $path) {
                                $files[] = FileUtils::getModuleRootPath($module) . "/" . $path;
                            }
                        } else {
                            $files[] = FileUtils::getModuleRootPath($module) . "/" . $row['path'];
                        }
                    } elseif ($row['type'] === 'folder') {
                        $path = FileUtils::getModuleRootPath($module) . "/" . $row['path'];
                        $folderFiles = FileUtils::getFiles(
                            $path,
                            "~\.$type$~",
                            $row['recursive'] ?? false
                        );
                        if (isset($row['ignoreFilenames'])) {
                            foreach ($folderFiles as $key => $file) {
                                if (in_array(basename($file), $row['ignoreFilenames'])) {
                                    unset($folderFiles[$key]);
                                }
                            }
                        }
                        $files = array_merge(
                            $files,
                            $folderFiles
                        );
                    }
                }
                // remove dupes
                $compileFiles = [];
                foreach ($files as $file) {
                    $file = realpath($file);
                    if (!isset($compileFiles[$file])) {
                        $compileFiles[$file] = $file;
                    }
                }
                $compileFiles = array_values($compileFiles);
                // check if there need to be an update based on filetimes
                $compilerRequired = true;
                if (!$forceUpdate && file_exists($distFilePath)) {
                    $compilerRequired = false;
                    $distFileTimestamp = filemtime($distFilePath);
                    foreach ($compileFiles as $file) {
                        if (filemtime($file) > $distFileTimestamp) {
                            $compilerRequired = true;
                            break;
                        }
                    }
                }
                unset($existingDistFiles[$distFilePath]);
                // skip if no files exist
                if (!$files) {
                    continue;
                }
                // skip if we are already up 2 date
                if (!$compilerRequired && !$forceUpdate) {
                    continue;
                }
                // pass to nodejs compiler script
                $cmdParams = [
                    'type' => $type,
                    'distFilePath' => $distFilePath,
                    'files' => $compileFiles,
                    'options' => $groupData['options'] ?? []
                ];
                $shell = Shell::prepare("node {*}",
                    [
                        FileUtils::getModuleRootPath("Framelix") . "/nodejs/compiler.js",
                        base64_encode(JsonUtils::encode($cmdParams))
                    ]
                );
                $shell->execute();
                if ($shell->status) {
                    throw new Exception(
                        implode("\n", $shell->output),
                        ErrorCode::COMPILER_COMPILE_ERROR
                    );
                }
                $returnDistFiles[] = $distFilePath;
                touch($distFilePath);
                Toast::success(basename($distFilePath) . " compiled successfully");
            }
        }
        // delete old files
        foreach ($existingDistFiles as $existingDistFile) {
            unlink($existingDistFile);
        }
        // write compiler data to meta file
        JsonUtils::writeToFile($metaFilePath, $compilerData);
        self::$cache[$module] = $compilerData;
        return $returnDistFiles;
    }
}