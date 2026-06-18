<?php

namespace App\Services\Extraction;

use App\DTOs\ExtractionResult;
use App\Services\Extraction\Contracts\Extractor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LlamaParseExtractor implements Extractor
{
    private LlamaParseService $service;

    public function __construct(?LlamaParseService $service = null)
    {
        $this->service = $service ?? app(LlamaParseService::class);
    }

    public function isAvailable(): bool
    {
        return $this->service->isAvailable();
    }

    public function extract(string $filePath): ExtractionResult
    {
        if (! $this->isAvailable()) {
            return ExtractionResult::unavailable('LlamaParseExtractor');
        }

        $jobKey = 'llamaparse_job_'.md5($filePath);
        $jobId = Cache::get($jobKey);

        if ($jobId === null) {
            $result = $this->service->startParsing($filePath);

            if ($result['status'] === 'started' && isset($result['job_id'])) {
                Cache::put($jobKey, (string) $result['job_id'], 300);

                return ExtractionResult::pending('LlamaParseExtractor');
            }

            if ($result['status'] === 'error') {
                Log::warning('LlamaParse failed to start', [
                    'error' => $result['error'] ?? 'Unknown',
                ]);

                return ExtractionResult::failed('LlamaParseExtractor', $result['error'] ?? 'Failed to start');
            }

            return ExtractionResult::unavailable('LlamaParseExtractor');
        }

        $status = $this->service->checkJobStatus($jobId);

        if ($status['status'] === 'completed') {
            Cache::forget($jobKey);

            return ExtractionResult::completed(
                $this->truncateText($status['result'] ?? ''),
                'LlamaParseExtractor',
            );
        }

        if (in_array($status['status'], ['failed', 'error'])) {
            Cache::forget($jobKey);
            Log::warning('LlamaParse job failed', [
                'status' => $status['status'],
                'error' => $status['error'] ?? 'Unknown',
            ]);

            return ExtractionResult::failed('LlamaParseExtractor', $status['error'] ?? 'Job failed');
        }

        return ExtractionResult::pending('LlamaParseExtractor');
    }

    private function truncateText(string $text): string
    {
        if (strlen($text) <= 30000) {
            return $text;
        }

        return substr($text, 0, 30000)
            ."\n\n[... CV content truncated at 30000 characters. Only the beginning was included for processing. ...]";
    }
}
