<?php

namespace App\Jobs;

use App\Ai\Agents\CandidateInfoExtractor;
use App\Services\AiClient;
use App\Services\Extraction\ExtractionOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExtractCandidateInfoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const MAX_INPUT_LENGTH = 30000;

    public int $tries = 30;

    public function __construct(
        public string $cvPath,
        public string $cacheKey,
    ) {}

    public function handle(): void
    {
        try {
            $orchestrator = app(ExtractionOrchestrator::class);
            $result = $orchestrator->extract($this->cvPath);

            if ($result->isPending()) {
                $this->release(5);

                return;
            }

            if ($result->isFailed()) {
                Log::error('All extractors failed', [
                    'error' => $result->errorMessage,
                ]);
                Cache::put($this->cacheKey, [
                    'status' => 'error',
                    'message' => 'Could not extract candidate information. Please enter the details manually.',
                ], 300);

                return;
            }

            $extractedText = $result->content;

            if (strlen($extractedText) < 50) {
                Cache::put($this->cacheKey, [
                    'status' => 'warning',
                    'message' => 'Could not extract text from this PDF. The file may be a scanned image. Please enter candidate info manually.',
                ], 300);

                return;
            }

            $agent = new CandidateInfoExtractor;
            $response = app(AiClient::class)->prompt($agent, $extractedText);

            $data = $this->parseJsonResponse($response);

            $sections = $this->buildSections($data);

            $this->storeResult([
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'summary' => $data['summary'] ?? null,
                'sections' => $sections,
            ], $extractedText, $data);
        } catch (\Exception $e) {
            Log::error('ExtractCandidateInfoJob failed', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Cache::put($this->cacheKey, [
                'status' => 'error',
                'message' => 'Could not extract candidate information. Please enter the details manually.',
            ], 300);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonResponse(string $response): array
    {
        $json = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }

        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $json = json_decode($matches[1], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return $json;
            }
        }

        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $json = json_decode($matches[0], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return $json;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array{title: string, items: array<int, string>}>
     */
    private function buildSections(array $response): array
    {
        $sectionMap = [
            'education' => 'Education',
            'experience' => 'Experience',
            'skills' => 'Skills',
            'certifications' => 'Certifications',
            'languages' => 'Languages',
            'projects' => 'Projects',
            'other_sections' => 'Other',
        ];

        $sections = [];

        foreach ($sectionMap as $key => $title) {
            $value = $response[$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $items = array_filter(array_map('trim', $value));
            } else {
                $items = array_filter(array_map('trim', explode("\n", $value)));
            }

            $items = array_map(function ($item) {
                return ltrim((string) $item, '- ');
            }, $items);

            if (count($items) > 0) {
                $sections[] = [
                    'title' => $title,
                    'items' => array_values($items),
                ];
            }
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rawPayload
     */
    private function storeResult(array $data, string $extractedText, array $rawPayload = []): void
    {
        Cache::put($this->cacheKey, [
            'status' => 'success',
            'extracted_text' => $extractedText,
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'summary' => $data['summary'] ?? null,
            'sections' => $data['sections'] ?? [],
            'extraction_payload' => $rawPayload,
        ], 300);
    }
}
