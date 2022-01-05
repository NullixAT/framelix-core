<?php

namespace Framelix\Framelix\Form\Field;

use Framelix\Framelix\Form\Field;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\StorableFile;

use function is_array;

/**
 * A file upload field
 */
class File extends Field
{
    /**
     * Max width in pixel or other unit
     * @var int|string|null
     */
    public int|string|null $maxWidth = 700;

    /**
     * Is multiple
     * @var bool
     */
    public bool $multiple = false;

    /**
     * Allowed file types
     * Example: Only allow images, use image/*, allow only certain file endings use .txt for example
     * @var string|null
     */
    public ?string $allowedFileTypes = null;

    /**
     * Min selected files for submitted value
     * @var int|null
     */
    public ?int $minSelectedFiles = null;

    /**
     * Max selected files for submitted value
     * @var int|null
     */
    public ?int $maxSelectedFiles = null;

    /**
     * Upload btn label
     * @var string
     */
    public string $buttonLabel = '__framelix_form_file_pick__';

    /**
     * Get default converted submitted value
     * @return UploadedFile[]|null
     */
    public function getDefaultConvertedSubmittedValue(): ?array
    {
        return UploadedFile::createFromSubmitData($this->name);
    }

    /**
     * Set allowing only images
     * @return void
     */
    public function setOnlyImages(): void
    {
        $this->allowedFileTypes = '.jpg, .jpeg, .gif, .png, .webp';
    }

    /**
     * Set allowing only videos
     * @return void
     */
    public function setOnlyVideos(): void
    {
        $this->allowedFileTypes = '.mp4, .webm';
    }

    /**
     * Validate
     * Return error message on error or true on success
     * @return string|bool
     */
    public function validate(): string|bool
    {
        if (!$this->isVisible()) {
            return true;
        }
        $parentValidation = parent::validate();
        if ($parentValidation !== true) {
            return $parentValidation;
        }
        $value = $this->getDefaultConvertedSubmittedValue();
        $count = is_array($value) ? count($value) : 0;
        if ($count) {
            if ($this->minSelectedFiles !== null && $count < $this->minSelectedFiles) {
                return Lang::get(
                    '__framelix_form_validation_minselectedfiles__',
                    ['number' => $this->minSelectedFiles]
                );
            }
            if ($this->maxSelectedFiles !== null && $count > $this->maxSelectedFiles) {
                return Lang::get(
                    '__framelix_form_validation_maxselectedfiles__',
                    ['number' => $this->maxSelectedFiles]
                );
            }
        }
        return true;
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        if ($this->defaultValue) {
            $files = !is_array($this->defaultValue) ? [$this->defaultValue] : $this->defaultValue;
            $defaultValue = [];
            foreach ($files as $file) {
                if (!$file instanceof StorableFile) {
                    continue;
                }
                $defaultValue[] = [
                    'id' => $file->id,
                    'name' => $file->filename,
                    'size' => $file->filesize,
                    'url' => $file->getDownloadUrl()
                ];
            }
            $data['properties']['defaultValue'] = $defaultValue ?: null;
        }
        return $data;
    }


}