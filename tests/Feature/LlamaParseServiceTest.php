<?php

use App\Services\Extraction\LlamaParseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::put('pdfs/test.pdf', 'fake PDF content');

    config()->set('services.llamaindex.key', 'test-key');
    config()->set('services.llamaindex.url', 'https://api.llamaindex.test');
});

test('isAvailable returns true when api key is set', function () {
    $service = new LlamaParseService;

    expect($service->isAvailable())->toBeTrue();
});

test('isAvailable returns false when api key is empty', function () {
    config()->set('services.llamaindex.key', '');

    $service = new LlamaParseService;

    expect($service->isAvailable())->toBeFalse();
});

test('startParsing returns unavailable when no api key', function () {
    config()->set('services.llamaindex.key', '');

    $service = new LlamaParseService;

    $result = $service->startParsing('pdfs/test.pdf');

    expect($result['status'])->toBe('unavailable');
});

test('checkJobStatus returns completed with markdown', function () {
    Http::fake([
        'https://api.llamaindex.test/api/v2/parse/*' => Http::response([
            'job' => ['status' => 'COMPLETED'],
            'markdown_full' => '# Test CV
John Doe

## Experience
Developer at ACME',
        ], 200),
    ]);

    $service = new LlamaParseService;

    $result = $service->checkJobStatus('job_456');

    expect($result['status'])->toBe('completed');
    expect($result['result'])->toContain('Test CV');
    expect($result['result'])->toContain('John Doe');
});

test('checkJobStatus returns failed', function () {
    Http::fake([
        'https://api.llamaindex.test/api/v2/parse/*' => Http::response([
            'job' => [
                'status' => 'FAILED',
                'error_message' => 'Invalid PDF format',
            ],
        ], 200),
    ]);

    $service = new LlamaParseService;

    $result = $service->checkJobStatus('job_456');

    expect($result['status'])->toBe('failed');
    expect($result['error'])->toBe('Invalid PDF format');
});

test('checkJobStatus returns processing', function () {
    Http::fake([
        'https://api.llamaindex.test/api/v2/parse/*' => Http::response([
            'job' => ['status' => 'PROCESSING'],
        ], 200),
    ]);

    $service = new LlamaParseService;

    $result = $service->checkJobStatus('job_456');

    expect($result['status'])->toBe('processing');
});

test('checkJobStatus returns error on exception', function () {
    Http::fake([
        'https://api.llamaindex.test/api/v2/parse/*' => Http::response([], 500),
    ]);

    $service = new LlamaParseService;

    $result = $service->checkJobStatus('job_456');

    expect($result['status'])->toBe('error');
});

test('extractMarkdown returns markdown_full when available', function () {
    Http::fake([
        'https://api.llamaindex.test/api/v2/parse/*' => Http::response([
            'job' => ['status' => 'COMPLETED'],
            'markdown_full' => 'Full markdown content',
        ], 200),
    ]);

    $service = new LlamaParseService;

    $result = $service->checkJobStatus('job_456');

    expect($result['result'])->toBe('Full markdown content');
});
