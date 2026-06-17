<?php

namespace App\Services\Extraction;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class LlamaParseService
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.llamaindex.key', '');
        $this->baseUrl = config('services.llamaindex.url', 'https://api.cloud.llamaindex.ai');
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Parse a PDF file using LlamaParse and return structured candidate data.
     * Blocks synchronously (used in non-queue contexts like debug routes).
     *
     * @return array{status: string, data?: array<string, mixed>, error?: string}
     */
    public function parsePdf(string $filePath): array
    {
        if (! $this->isAvailable()) {
            return ['status' => 'unavailable'];
        }

        try {
            $fileId = $this->uploadFile($filePath);
            $jobId = $this->startParseJob($fileId);
            $result = $this->pollJob($jobId);

            if ($result['status'] !== 'completed') {
                return ['status' => 'error', 'error' => $result['error'] ?? 'Parsing failed'];
            }

            return ['status' => 'success', 'data' => [
                'extracted_text' => $result['result'] ?? '',
                'raw_keys' => array_keys($result['raw'] ?? []),
            ]];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload a file and start a parse job, returning the job ID.
     * Non-blocking — caller is responsible for polling.
     *
     * @return array{status: string, job_id?: string, error?: string}
     */
    public function startParsing(string $filePath): array
    {
        if (! $this->isAvailable()) {
            return ['status' => 'unavailable'];
        }

        try {
            $fileId = $this->uploadFile($filePath);
            $jobId = $this->startParseJob($fileId);

            return ['status' => 'started', 'job_id' => $jobId];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Poll job status until completion, failure, or timeout.
     *
     * @return array{status: string, result?: string, error?: string, raw?: array<mixed>}
     */
    public function checkJobStatus(string $jobId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->get("{$this->baseUrl}/api/v2/parse/{$jobId}?expand=markdown_full,markdown,text_full");

            $response->throw();

            $data = $response->json();
            $job = $data['job'] ?? $data;
            $status = $job['status'] ?? 'unknown';

            if ($status === 'COMPLETED') {
                $markdown = $this->extractMarkdown($data);

                return ['status' => 'completed', 'result' => $markdown, 'raw' => $data];
            }

            if (in_array($status, ['FAILED', 'CANCELLED'])) {
                return ['status' => 'failed', 'error' => $job['error_message'] ?? 'Unknown error', 'raw' => $data];
            }

            return ['status' => 'processing'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function uploadFile(string $filePath): string
    {
        $fullPath = Storage::path($filePath);

        $ch = curl_init("{$this->baseUrl}/api/v1/beta/files");
        $cfile = new \CURLFile($fullPath, 'application/pdf', basename($filePath));

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => $cfile, 'purpose' => 'parse'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$this->apiKey,
                'Accept: application/json',
                'Expect: ',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('LlamaParse upload curl error: '.$error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        /** @var string $response */
        $data = json_decode($response, true);

        if ($httpCode >= 400 || empty($data['id'])) {
            throw new \RuntimeException('Upload failed ('.$httpCode.'): '.$response);
        }

        return $data['id'];
    }

    private function startParseJob(string $fileId): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/api/v2/parse", [
            'file_id' => $fileId,
            'tier' => 'cost_effective',
            'version' => 'latest',
            'page_ranges' => [
                'max_pages' => 100,
            ],
        ]);

        $response->throw();

        return $response->json('id');
    }

    /**
     * @return array{status: string, result?: string, error?: string, raw?: array<mixed>}
     */
    private function pollJob(string $jobId, int $maxAttempts = 30, int $delayMs = 2000): array
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $result = $this->checkJobStatus($jobId);

            if ($result['status'] === 'completed') {
                return $result;
            }

            if (in_array($result['status'], ['failed', 'error'])) {
                return $result;
            }

            if ($i < $maxAttempts - 1) {
                usleep($delayMs * 1000);
            }
        }

        return ['status' => 'timeout', 'error' => 'LlamaParse timed out'];
    }

    /**
     * @param  array<mixed>  $data
     */
    private function extractMarkdown(array $data): string
    {
        if (isset($data['markdown_full']) && is_string($data['markdown_full'])) {
            return $data['markdown_full'];
        }

        if (isset($data['markdown']) && is_string($data['markdown'])) {
            return $data['markdown'];
        }

        if (isset($data['markdown']['markdown']) && is_string($data['markdown']['markdown'])) {
            return $data['markdown']['markdown'];
        }

        if (isset($data['markdown']['pages']) && is_array($data['markdown']['pages'])) {
            $result = '';
            foreach ($data['markdown']['pages'] as $page) {
                $result .= $page['markdown'] ?? $page['text'] ?? $page['content'] ?? '';
            }

            return $result;
        }

        if (isset($data['job']['result']) && is_string($data['job']['result'])) {
            return $data['job']['result'];
        }

        if (isset($data['result']) && is_string($data['result'])) {
            return $data['result'];
        }

        return '';
    }
}
