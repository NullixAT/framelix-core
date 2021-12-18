<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\Config;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Utils\Buffer;
use Framelix\Framelix\Utils\JsonUtils;

use function basename;
use function file_exists;
use function filesize;
use function header;
use function http_response_code;
use function implode;
use function nl2br;
use function readfile;
use function strlen;
use function substr;

/**
 * Response utilities for frequent tasks
 */
class Response
{
    /**
     * Initialize a file download for the browser
     * @param string|StorableFile $fileOrData If starting with @, the parameter will be threaded as string rather than file
     * @param string|null $filename
     * @param string|null $filetype
     * @return never
     */
    public static function download(
        string|StorableFile $fileOrData,
        ?string $filename = null,
        ?string $filetype = "application/octet-stream"
    ): never {
        Buffer::clear();
        if ($fileOrData instanceof StorableFile) {
            $filename = $fileOrData->filename;
            $isFile = true;
            $fileOrData = $fileOrData->getPath();
            if (!$fileOrData) {
                echo "File does not exist on disk";
                die();
            }
        } else {
            $isFile = !str_starts_with($fileOrData, "@");
            if (!$isFile) {
                $fileOrData = substr($fileOrData, 1);
            }
        }
        if ($isFile && !file_exists($fileOrData)) {
            http_response_code(404);
            die();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $filetype);
        header(
            'Content-Disposition: attachment; filename="' . basename(
                $isFile && !$filename ? basename($fileOrData) : $filename ?? "download.txt"
            ) . '"'
        );
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . ($isFile ? filesize($fileOrData) : strlen($fileOrData)));
        if ($isFile) {
            readfile($fileOrData);
        } else {
            echo $fileOrData;
        }
        die();
    }

    /**
     * Show a form async submit response, used after successfully async submit when no redirect is required
     * If Toast messages where issued, then the messages will be displayed as well
     * @param string|null $modalMessage If set, then display this message in a modal window
     * @param bool $reloadTab If the form was submitted inside a tab view, then just reload the tab
     * @return never
     */
    public static function showFormAsyncSubmitResponse(?string $modalMessage = null, bool $reloadTab = false): never
    {
        http_response_code(200);
        header('x-form-async-response: 1');
        JsonUtils::output([
            'modalMessage' => $modalMessage,
            'reloadTab' => $reloadTab,
            'toastMessages' => Toast::getQueueMessages(true)
        ]);
    }

    /**
     * Show a form validation error response, used for async form validation
     * @param array|string|null $messages If an array, then keys are form field names, if field is not found, it will throw the error bellow the form button
     * @return never
     */
    public static function showFormValidationErrorResponse(array|string|null $messages): never
    {
        http_response_code(406);
        JsonUtils::output(nl2br($messages));
    }


}