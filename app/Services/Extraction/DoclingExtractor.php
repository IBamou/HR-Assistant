<?php

namespace App\Services\Extraction;

use App\DTOs\ExtractionResult;
use App\Services\Extraction\Contracts\Extractor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DoclingExtractor implements Extractor
{
    private string $baseUrl;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.docling.url', 'http://localhost:8000');
        $this->timeout = (int) config('services.docling.timeout', 120);
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");

            return $response->successful() && $response->json('status') === 'ok';
        } catch (\Exception) {
            return false;
        }
    }

    public function extract(string $filePath): ExtractionResult
    {
        try {
            $stream = Storage::readStream($filePath);

            if (! $stream) {
                return ExtractionResult::failed('DoclingExtractor', "Cannot read file: {$filePath}");
            }

            $response = Http::timeout($this->timeout)
                ->attach('file', $stream, basename($filePath))
                ->post("{$this->baseUrl}/parse");

            if (! $response->successful()) {
                return ExtractionResult::failed('DoclingExtractor', "HTTP {$response->status()}: {$response->body()}");
            }

            $data = $response->json();

            if (! is_array($data) || empty($data['success']) || ! isset($data['content'])) {
                return ExtractionResult::failed('DoclingExtractor', 'Invalid response from Docling service');
            }

            return ExtractionResult::completed((string) $data['content'], 'DoclingExtractor');
        } catch (\Exception $e) {
            return ExtractionResult::failed('DoclingExtractor', $e->getMessage());
        }
    }
}
