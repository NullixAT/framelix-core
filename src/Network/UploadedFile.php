<?php

namespace Framelix\Framelix\Network;

use function file_get_contents;
use function is_array;
use function is_string;
use function strrpos;
use function strtolower;
use function substr;

/**
 * A uploaded file wrapper for $_FILES
 */
class UploadedFile
{
    /**
     * The uploaded file name
     * @var string
     */
    public string $name;

    /**
     * The tmp path to the file on disk
     * @var string
     */
    public string $path;

    /**
     * Filesize
     * @var int
     */
    public int $size;

    /**
     * The mime type
     * @var string
     */
    public string $type;

    /**
     * Return array of instances for all data in $_FILES
     * @param string $fieldName
     * @return self[]|null
     */
    public static function createFromSubmitData(string $fieldName): ?array
    {
        $submittedFiles = $_FILES[$fieldName] ?? null;
        if (!is_array($submittedFiles)) {
            return null;
        }
        if (is_string($submittedFiles['name'])) {
            $newArr = [];
            foreach ($submittedFiles as $key => $row) {
                $newArr[$key][0] = $row;
            }
            $submittedFiles = $newArr;
        }
        $arr = [];
        foreach ($submittedFiles['name'] as $fileKey => $name) {
            if ($submittedFiles['error'][$fileKey]) {
                continue;
            }
            $instance = new self();
            $instance->name = $name;
            $instance->path = $submittedFiles['tmp_name'][$fileKey];
            $instance->size = (int)$submittedFiles['size'][$fileKey];
            $instance->type = $submittedFiles['type'][$fileKey];
            $arr[] = $instance;
        }
        return $arr ?: null;
    }

    /**
     * Get filedata
     * @return string
     */
    public function getFileData(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * Get file extension
     * @return string|null
     */
    public function getExtension(): ?string
    {
        $lastPoint = strrpos($this->name, ".");
        if ($lastPoint !== false) {
            return substr(strtolower(substr($this->name, $lastPoint + 1)), 0, 20);
        }
        return null;
    }
}