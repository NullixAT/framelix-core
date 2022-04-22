<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Db\StorableSchema;
use Framelix\Framelix\ErrorCode;
use Framelix\Framelix\Exception;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\View;

use function ceil;
use function copy;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filesize;
use function in_array;
use function is_dir;
use function is_string;
use function mkdir;
use function strrpos;
use function strtolower;
use function substr;
use function unlink;

/**
 * A storable file to store on disk
 * @property string $filename
 * @property string|null $extension
 * @property string $relativePathOnDisk
 * @property int $filesize
 * @property Storable|null $assignedStorable
 * @property int $fileNr Internal file nr counter for all files that are stored
 */
abstract class StorableFile extends StorableExtended
{
    /**
     * The folder on disk to store the file in
     * The system does create more folders in this folder, to separate files, based on $maxFilesPerFolder setting
     * @var string|null
     */
    public ?string $folder = null;

    /**
     * Max files in a single subfolder
     * If limit is reached, it does create a new folder
     * @var int
     */
    protected int $maxFilesPerFolder = 1000;

    /**
     * Keep file extensions on disk
     * The files that are not matches these extensions stored on disk as {filename}.txt to prevent any abuse
     * By default, only images/videos are considered safe to keep and they are useful when you want link them directly on a website
     * @var string[]
     */
    protected array $keepFileExtensions = ['jpg', 'jpeg', 'gif', 'png', 'apng', 'svg', 'webp', 'mp4', 'webm'];

    /**
     * Setup self storable schema
     * @param StorableSchema $selfStorableSchema
     */
    protected static function setupStorableSchema(StorableSchema $selfStorableSchema): void
    {
        parent::setupStorableSchema($selfStorableSchema);
        $selfStorableSchema->properties['filesize']->databaseType = 'bigint';
        $selfStorableSchema->properties['filesize']->unsigned = true;
    }

    /**
     * Get download url
     * Return null if you want to disable download functionality
     * @return Url|null
     */
    public function getDownloadUrl(): ?Url
    {
        if (!$this->id) {
            return null;
        }
        return View::getUrl(View\Api::class, ['requestMethod' => 'downloadFile'])->setParameter(
            'id',
            $this
        )->setParameter(
            'connectionId',
            $this->connectionId
        )->sign();
    }

    /**
     * Get path to the file on disk
     * @param bool $fileCheck If true, then does return the path only if the file really exists
     * @return string|null Null if fileCheck is enabled an file do not exist on disk
     */
    public function getPath(bool $fileCheck = true): ?string
    {
        $path = $this->folder . "/" . $this->relativePathOnDisk;
        if ($fileCheck && !file_exists($path)) {
            return null;
        }
        return $path;
    }

    /**
     * Get filedata
     * @return string|null
     */
    public function getFiledata(): ?string
    {
        $path = $this->getPath();
        if ($path) {
            return file_get_contents($path);
        }
        return null;
    }

    /**
     * Get a human-readable raw text representation of this instace
     * @return string
     */
    public function getRawTextString(): string
    {
        return $this->filename;
    }

    /**
     * Get a human-readable html representation of this instace
     * @return string
     */
    public function getHtmlString(): string
    {
        $downloadUrl = $this->getDownloadUrl();
        if (!$downloadUrl) {
            return $this->getRawTextString();
        }
        return '<a href="' . $downloadUrl . '" title="__framelix_downloadfile__">' . $this->getRawTextString() . '</a>';
    }

    /**
     * Store with given file
     * If UploadedFile is given, it does MOVE the file, not COPY it
     * @param UploadedFile|string|null $file String is considered as binary filedata
     */
    public function store(UploadedFile|string|null $file = null): void
    {
        if ($file instanceof UploadedFile && !file_exists($file->path)) {
            throw new Exception(
                "Couldn't store StorableFile because uploaded file does not exist",
                ErrorCode::STORABLEFILE_FILE_NOTEXIST
            );
        }
        if (!is_dir($this->folder)) {
            throw new Exception(
                "Couldn't store StorableFile because folder to store in is not a directory",
                ErrorCode::STORABLEFILE_FOLDER_NOTEXIST
            );
        }
        if ($file === null && !$this->id) {
            throw new Exception(
                "Couldn't store StorableFile because no file is given",
                ErrorCode::STORABLEFILE_FILE_MISSING
            );
        }
        if ($file instanceof UploadedFile && !$this->filename) {
            $this->filename = $file->name;
        }
        if (!$this->filename) {
            throw new Exception("You need to set a filename", ErrorCode::STORABLEFILE_FILENAME_MISSING);
        }
        $lastPoint = strrpos($this->filename, ".");
        $this->filename = substr($this->filename, -190);
        if ($lastPoint !== false) {
            $this->extension = substr(strtolower(substr($this->filename, $lastPoint + 1)), 0, 20);
        }
        // no file given, store just the metadata
        if ($file === null) {
            parent::store();
            return;
        }
        if (!$this->id) {
            $lastFile = static::getByConditionOne(sort: ['-id']);
            $fileNr = 1;
            if ($lastFile) {
                $fileNr = $lastFile->fileNr + 1;
            }
            $extensionOnDisk = (in_array($this->extension, $this->keepFileExtensions) ? $this->extension : 'txt');
            $folderName = ceil($fileNr / $this->maxFilesPerFolder) * $this->maxFilesPerFolder;
            while (true) {
                $this->relativePathOnDisk .= $folderName . "/" . RandomGenerator::getRandomString(
                        30,
                        40
                    ) . "." . $extensionOnDisk;
                // file not exist, break the loop
                if (!$this->getPath()) {
                    break;
                }
            }

            $folder = $this->folder . "/" . $folderName;
            if (!is_dir($folder)) {
                mkdir($folder);
            }
            $this->fileNr = $fileNr;
        }
        $path = $this->getPath(false);
        if ($file instanceof UploadedFile) {
            if (!copy($file->path, $path)) {
                // @codeCoverageIgnoreStart
                throw new Exception("Couldn't copy file to destination folder", ErrorCode::STORABLEFILE_COPY_FAILURE);
                // @codeCoverageIgnoreEnd
            }
            if (file_exists($file->path)) {
                unlink($file->path);
            }
            $this->filesize = $file->size;
        } elseif (is_string($file)) {
            file_put_contents($path, $file);
            $this->filesize = filesize($path);
        }
        parent::store();
    }

    /**
     * Delete
     * @param bool $force
     * @return void
     */
    public function delete(bool $force = false): void
    {
        $path = $this->getPath();
        parent::delete($force);
        if ($path) {
            unlink($path);
        }
    }
}