<?php

namespace App\Services\Extraction\Contracts;

use App\DTOs\ExtractionResult;

interface Extractor
{
    public function isAvailable(): bool;

    public function extract(string $filePath): ExtractionResult;
}
