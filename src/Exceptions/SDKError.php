<?php

declare(strict_types=1);

namespace Receeco\Exceptions;

use Exception;

/**
 * Base exception class for ReCeeco SDK errors.
 */
class SDKError extends Exception
{
    private string $code;

    /**
     * Create a new SDK error.
     *
     * @param string $code Error code
     * @param string $message Error message
     */
    public function __construct(string $code, string $message)
    {
        $this->code = $code;
        parent::__construct($message);
    }

    /**
     * Get the error code.
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->code;
    }

    /**
     * Get string representation of the error.
     *
     * @return string
     */
    public function __toString(): string
    {
        return "ReceecoSDKError [{$this->code}]: {$this->getMessage()}";
    }
} 