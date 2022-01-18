<?php

namespace Framelix\Framelix\Utils;

use function ob_end_clean;
use function ob_get_contents;
use function ob_get_level;

/**
 * Output buffer handling
 * This allow output buffering without affecting prev existing buffers
 */
class Buffer
{
    /**
     * Store reference to buffer index when framelix script execution starts
     * This is required for unit tests, as they have already buffers opened before framelix comes
     * @var int
     */
    public static int $startBufferIndex = 0;

    /**
     * Start a new output buffer
     */
    public static function start(): void
    {
        ob_start();
    }

    /**
     * Clear complete output buffer
     */
    public static function clear(): void
    {
        while (ob_get_level() > self::$startBufferIndex) {
            ob_end_clean();
        }
    }

    /**
     * Flush complete output buffer
     * @codeCoverageIgnore Currently not found a way to test this without breaking php unit
     */
    public static function flush(): void
    {
        while (ob_get_level() > self::$startBufferIndex) {
            ob_end_flush();
        }
    }

    /**
     * Get last started output buffer as string and empty the output buffer after that
     * @return string
     */
    public static function get(): string
    {
        if (ob_get_level() > self::$startBufferIndex) {
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
        while (ob_get_level() > self::$startBufferIndex) {
            $outputBuffer .= ob_get_contents();
            ob_end_clean();
        }
        return $outputBuffer;
    }
}