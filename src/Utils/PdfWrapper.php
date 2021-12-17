<?php

namespace Framelix\Framelix\Utils;

use TCPDF;

use function call_user_func_array;

require_once FileUtils::getModuleRootPath("Framelix") . "/vendor/tcpdf/tcpdf.php";

/**
 * Class PdfWrapper
 */
class PdfWrapper extends TCPDF
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
     * Header
     */
    public function Header()
    {
        if ($this->header) {
            call_user_func_array($this->header, []);
        }
    }

    /**
     * Footer
     */
    public function Footer()
    {
        if ($this->footer) {
            call_user_func_array($this->footer, []);
        }
    }
}