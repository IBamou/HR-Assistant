<?php

namespace App\Services\Extraction\Exceptions;

use Exception;

class ExtractionFailedException extends Exception
{
    public function __construct(
        string $message = 'Extraction failed',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
