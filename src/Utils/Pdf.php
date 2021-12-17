<?php

namespace Framelix\Framelix\Utils;

use Exception;
use Framelix\Framelix\Network\Response;

use function base64_encode;
use function call_user_func_array;
use function file_put_contents;

/**
 * Class Pdf
 */
class Pdf
{
    /**
     * A callable to execute for every page header
     * @var callable|null
     */
    public $header = null;

    /**
     * A callable to execute for every page footer
     * @var callable|null
     */
    public $footer = null;

    /**
     * The tcpdf instance
     * @var PdfWrapper
     */
    public $tcpdf;

    /**
     * Stylesheet
     * @var string|null
     */
    public ?string $styleheet = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tcpdf = new PdfWrapper();
        $this->tcpdf->SetFont('dejavusans', '', 12);
        $this->tcpdf->header = function () {
            if ($this->header) {
                call_user_func_array($this->header, []);
            }
        };
        $this->tcpdf->footer = function () {
            if ($this->footer) {
                call_user_func_array($this->footer, []);
            }
        };
    }

    /**
     * Write html to pdf
     * @param string $html
     */
    public function write(string $html): void
    {
        $this->tcpdf->writeHTML($this->styleheet ? $this->styleheet . "\n" . $html : $html, false);
    }

    /**
     * Start output capturing stylesheet
     */
    public function startStylesheet(): void
    {
        Buffer::start();
    }

    /**
     * End output capturing stylesheet and load the buffered html into pdf
     */
    public function endStylesheet(): void
    {
        $this->styleheet = Buffer::get();
    }

    /**
     * Get pdf data as binary string
     * @return string
     */
    public function getDataAsString(): string
    {
        if (!$this->tcpdf->getPage()) {
            throw new Exception("There is no page added to this pdf");
        }
        return $this->tcpdf->output('', 'S');
    }

    /**
     * Get pdf data as a base64 string
     * @return string
     */
    public function getDataAsBase64String(): string
    {
        return base64_encode($this->getDataAsString());
    }

    /**
     * Get pdf in the browser
     * @param string $filename
     */
    public function download(string $filename): void
    {
        Response::download($this->getDataAsString(), $filename);
    }

    /**
     * Save pdf to disk
     * @param string $filepath
     */
    public function saveToDisk(string $filepath): void
    {
        file_put_contents($filepath, $this->getDataAsString());
    }
}