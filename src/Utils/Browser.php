<?php

namespace Framelix\Framelix\Utils;

use function array_shift;
use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function explode;
use function strpos;
use function strtoupper;
use function substr;

use const CURLINFO_RESPONSE_CODE;
use const CURLOPT_HEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;

/**
 * Browser Utils - To mimic browser when calling urls or sending post data
 */
class Browser
{

    /**
     * The current curl handler
     * @var mixed
     */
    public mixed $curl = null;

    /**
     * The last request curl handler
     * @var mixed
     */
    public mixed $lastRequestCurl = null;

    /**
     * The url to send to
     * @var string
     */
    public string $url = '';

    /**
     * The request method
     * @var string
     */
    public string $requestMethod = 'get';

    /**
     * Headers to send with request
     * By default we simulate chrome
     * @var string[]
     */
    public array $sendHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36'
    ];

    /**
     * Parsed response headers from last request
     * @var string[]
     */
    public array $responseHeaders = [];

    /**
     * Raw response body from last request
     * @var string
     */
    public string $responseBody = '';

    /**
     * Error message from last request, if there is any
     * @var string|null
     */
    public ?string $requestError = null;

    /**
     * Validate ssl cert when using https
     * @var bool
     */
    public bool $validateSsl = true;

    /**
     * Create a new instance
     * @return self
     */
    public static function create(): self
    {
        $instance = new self();
        $instance->curl = curl_init();
        curl_setopt($instance->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($instance->curl, CURLOPT_HEADER, true);
        return $instance;
    }

    /**
     * Get response code from last request
     * @return int
     */
    public function getResponseCode(): int
    {
        if ($this->lastRequestCurl) {
            return (int)curl_getinfo($this->lastRequestCurl, CURLINFO_RESPONSE_CODE);
        }
        return 0;
    }

    /**
     * Send the request
     * @return void
     */
    public function sendRequest(): void
    {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, strtoupper($this->requestMethod));
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->sendHeaders);
        if (!$this->validateSsl) {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        }
        $responseData = curl_exec($this->curl);
        $error = curl_error($this->curl);
        curl_close($this->curl);
        $this->requestError = $error ?: null;
        $this->responseHeaders = [];
        if (!$this->requestError) {
            $bodyBegin = strpos($responseData, "\r\n\r\n");
            $headerData = substr($responseData, 0, $bodyBegin);
            $headerLines = explode("\r\n", $headerData);
            array_shift($headerLines);
            foreach ($headerLines as $headerLine) {
                $spl = explode(":", $headerLine, 2);
                $this->responseHeaders[$spl[0]] = substr($spl[1], 1);
            }
            $this->responseBody = substr($responseData, $bodyBegin + 4);
        }

        // setup new curl and save last curl
        $this->lastRequestCurl = $this->curl;
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }
}