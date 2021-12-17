<?php

namespace Framelix\Framelix\Utils;

use function ob_end_clean;
use function ob_get_contents;
use function ob_get_level;

/**
 * Output buffer handling
 */
class Buffer
{
    /**
     * Start a new output buffer
     */
    public static function start(): void
    {
        ob_start();
    }

    /**
     * Clear current output buffer
     */
    public static function clear(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Flush current output buffer
     */
    public static function flush(): void
    {
        while (ob_get_level()) {
            ob_end_flush();
        }
    }

    /**
     * Get last started output buffer as string and empty the output buffer after that
     * @return string
     */
    public static function get(): string
    {
        if (ob_get_level()) {
            $outputBuffer = ob_get_contents();
            ob_end_clean();
            return $outputBuffer;
        }
        return '';
    }

    /**
     * Get all output buffers as string and empty the output buffer after that
     * @return string
     */
    public static function getAll(): string
    {
        $outputBuffer = "";
        while (ob_get_level()) {
            $outputBuffer .= ob_get_contents();
            ob_end_clean();
        }
        return $outputBuffer;
    }
}