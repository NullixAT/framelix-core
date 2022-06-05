<?php

namespace Framelix\Framelix\Network;

use Framelix\Framelix\Framelix;
use Framelix\Framelix\Html\Toast;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Utils\JsonUtils;

use function basename;
use function call_user_func_array;
use function file_exists;
use function filesize;
use function header;
use function headers_sent;
use function http_response_code;
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
     * Send http header, but only if it is possible (no headers are sent)
     * @param string $header
     * @return void
     */
    public static function header(string $header): void
    {
        // @codeCoverageIgnoreStart
        if (headers_sent()) {
            return;
        }
        // @codeCoverageIgnoreEnd
        header($header);
    }

    /**
     * Initialize a file download for the browser
     * @param string|StorableFile $fileOrData If starting with @, the parameter will be threaded as string rather than file
     * @param string|null $filename
     * @param string|null $filetype
     * @param callable|null $afterDownload A hook after download before script execution stops
     * @return never
     */
    public static function download(
        string|StorableFile $fileOrData,
        ?string $filename = null,
        ?string $filetype = "application/octet-stream",
        ?callable $afterDownload = null
    ): never {
        if ($fileOrData instanceof StorableFile) {
            $filename = $fileOrData->filename;
            $isFile = true;
            $fileOrData = $fileOrData->getPath();
            if (!$fileOrData) {
                http_response_code(404);
                Framelix::stop();
            }
        } else {
            $isFile = !str_starts_with($fileOrData, "@");
            if (!$isFile) {
                $fileOrData = substr($fileOrData, 1);
            }
        }
        if ($isFile && !file_exists($fileOrData)) {
            http_response_code(404);
            Framelix::stop();
        }
        self::header('Content-Description: File Transfer');
        self::header('Content-Type: ' . $filetype);
        self::header(
            'Content-Disposition: attachment; filename="' . basename(
                $isFile && !$filename ? basename($fileOrData) : $filename ?? "download.txt"
            ) . '"'
        );
        self::header('Expires: 0');
        self::header('Cache-Control: must-revalidate');
        self::header('Pragma: public');
        if ($isFile) {
            readfile($fileOrData);
        } else {
            echo $fileOrData;
        }
        if ($afterDownload) {
            call_user_func_array($afterDownload, []);
        }
        Framelix::stop();
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
        self::header('x-form-async-response: 1');
        JsonUtils::output([
            'modalMessage' => $modalMessage,
            'reloadTab' => $reloadTab,
            'toastMessages' => Toast::getQueueMessages(true)
        ]);
        Framelix::stop();
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
        Framelix::stop();
    }
}