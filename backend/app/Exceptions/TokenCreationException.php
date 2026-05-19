<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class TokenCreationException extends RuntimeException
{
    public static function becauseCreationFailed(Throwable $previous): self
    {
        return new self('Unable to create API token.', 0, $previous);
    }
}
