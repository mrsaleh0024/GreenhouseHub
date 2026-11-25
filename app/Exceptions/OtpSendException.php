<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The code could not be sent: throttled, or the SMS gateway failed.
 * The message is safe to show to the user.
 */
class OtpSendException extends RuntimeException
{
    public function __construct(string $message, public readonly int $retryAfter = 0)
    {
        parent::__construct($message);
    }
}
