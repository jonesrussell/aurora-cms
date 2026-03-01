<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Exception;

final class StorageException extends AuroraException
{
    public function __construct(
        string $message,
        array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 'aurora:storage/error', 503, $context, $previous);
    }
}
