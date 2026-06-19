<?php

namespace App\Services\Extraction;

use App\DTOs\ExtractionResult;
use App\Services\Extraction\Contracts\Extractor;
use Illuminate\Support\Facades\Log;

class ExtractionOrchestrator
{
    /** @var Extractor[] */
    private array $extractors;

    public function __construct(Extractor ...$extractors)
    {
        $this->extractors = array_values($extractors);
    }

    public function extract(string $filePath): ExtractionResult
    {
        foreach ($this->extractors as $extractor) {
            if (! $extractor->isAvailable()) {
                continue;
            }

            $result = $extractor->extract($filePath);

            if ($result->isCompleted()) {
                return $result;
            }

            Log::warning('Extractor did not complete, trying next', [
                'extractor' => basename(str_replace('\\', '/', $extractor::class)),
                'status' => $result->status,
                'error' => $result->errorMessage,
            ]);
        }

        return ExtractionResult::failed(
            'ExtractionOrchestrator',
            'All extractors failed to extract text',
        );
    }
}
