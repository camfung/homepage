<?php

declare(strict_types=1);

namespace TrafficPortal\Exception;

use Exception;

/**
 * Base API Exception
 *
 * Base exception for all API-related errors
 *
 * @package TrafficPortal\Exception
 */
class ApiException extends Exception
{
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
