<?php

namespace Tests\Unit;

use App\Exceptions\TokenCreationException;
use App\Exceptions\TranslationPersistenceException;
use RuntimeException;
use Tests\TestCase;

class ExceptionFactoryTest extends TestCase
{
    public function test_token_creation_exception_wraps_previous_exception(): void
    {
        $previous = new RuntimeException('database down');
        $exception = TokenCreationException::becauseCreationFailed($previous);

        $this->assertSame('Unable to create API token.', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_translation_persistence_exception_wraps_previous_exception(): void
    {
        $previous = new RuntimeException('write failed');
        $exception = TranslationPersistenceException::becausePersistenceFailed($previous);

        $this->assertSame('Unable to persist translation data.', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
