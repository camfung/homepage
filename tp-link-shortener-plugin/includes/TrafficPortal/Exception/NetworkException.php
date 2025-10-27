<?php

declare(strict_types=1);

namespace TrafficPortal\Exception;

/**
 * Network Exception
 *
 * Thrown when network-level errors occur (cURL errors, timeouts, etc.)
 *
 * @package TrafficPortal\Exception
 */
class NetworkException extends ApiException
{
}
