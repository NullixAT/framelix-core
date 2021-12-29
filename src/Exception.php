<?php

namespace Framelix\Framelix;


use Throwable;

/**
 * Framelix exception
 */
class Exception extends \Exception
{
    /**
     * Framelix error code
     * @var ErrorCode
     */
    public ErrorCode $framelixErrorCode = ErrorCode::NO_SPECIFIC;

    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link https://php.net/manual/en/exception.construct.php
     * @param string $message The Exception message to throw.
     * @param ErrorCode $code
     * @param null|Throwable $previous
     */
    public function __construct(
        string $message = "",
        ErrorCode $code = ErrorCode::NO_SPECIFIC,
        ?Throwable $previous = null
    ) {
        $this->framelixErrorCode = $code;
        parent::__construct($message, 0, $previous);
    }

}