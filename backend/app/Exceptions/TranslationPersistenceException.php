<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class TranslationPersistenceException extends RuntimeException
{
    public static function becausePersistenceFailed(Throwable $previous): self
    {
        return new self('Unable to persist translation data.', 0, $previous);
    }
}
