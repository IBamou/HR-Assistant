<?php

use App\DTOs\ExtractionResult;
use App\Services\Extraction\LlamaParseExtractor;
use App\Services\Extraction\LlamaParseService;
use Illuminate\Support\Facades\Cache;

test('isAvailable returns true when service is available', function () {
    $service = mock(LlamaParseService::class);
    $service->shouldReceive('isAvailable')->andReturnTrue();

    $extractor = new LlamaParseExtractor($service);

    expect($extractor->isAvailable())->toBeTrue();
});

test('isAvailable returns false when service is unavailable', function () {
    $service = mock(LlamaParseService::class);
    $service->shouldReceive('isAvailable')->andReturnFalse();

    $extractor = new LlamaParseExtractor($service);

    expect($extractor->isAvailable())->toBeFalse();
});

test('extract returns pending on first call with new job', function () {
    $service = mock(LlamaParseService::class);
    $service->shouldReceive('isAvailable')->andReturnTrue();
    $service->shouldReceive('startParsing')
        ->with('pdfs/test.pdf')
        ->andReturn(['status' => 'started', 'job_id' => 'job_456']);

    $extractor = new LlamaParseExtractor($service);
    $result = $extractor->extract('pdfs/test.pdf');

    expect($result)->toBeInstanceOf(ExtractionResult::class);
    expect($result->isPending())->toBeTrue();
    expect($result->extractorName)->toBe('LlamaParseExtractor');
});

test('extract returns pending when job is still processing', function () {
    Cache::put('llamaparse_job_'.md5('pdfs/test.pdf'), 'job_456', 300);

    $service = mock(LlamaParseService::class);
    $service->shouldReceive('isAvailable')->andReturnTrue();
    $service->shouldReceive('checkJobStatus')
        ->with('job_456')
        ->andReturn(['status' => 'processing']);

    $extractor = new LlamaParseExtractor($service);
    $result = $extractor->extract('pdfs/test.pdf');

    expect($result->isPending())->toBeTrue();
});

test('extract returns completed when job is done', function () {
    Cache::put('llamaparse_job_'.md5('pdfs/test.pdf'), 'job_456', 300);

    $service = mock(LlamaParseService::class);
    $service->shouldReceive('isAvailable')->andReturnTrue();
    $service->shouldReceive('checkJobStatus')
        ->with('job_456')
        ->andReturn(['status' => 'completed', 'result' => '# Parsed CV

John Doe']);

    $extractor = new LlamaParseExtractor($service);
    $result = $extractor->extract('pdfs/test.pdf');

    expect($result->isCompleted())->toBeTrue();
    expect($result->content)->toContain('Parsed CV');
});

test('extract returns failed when job failed', function () {
    Cache::put('llamaparse_job_'.md5('pdfs/test.pdf'), 'job_456', 300);

    $service = mock(LlamaParseService::class);
    $service->shouldReceive('isAvailable')->andReturnTrue();
    $service->shouldReceive('checkJobStatus')
        ->with('job_456')
        ->andReturn(['status' => 'failed', 'error' => 'Invalid PDF format']);

    $extractor = new LlamaParseExtractor($service);
    $result = $extractor->extract('pdfs/test.pdf');

    expect($result->isFailed())->toBeTrue();
    expect($result->errorMessage)->toBe('Invalid PDF format');
});

test('extract returns unavailable when service is not available', function () {
    $service = mock(LlamaParseService::class);
    $service->shouldReceive('isAvailable')->andReturnFalse();

    $extractor = new LlamaParseExtractor($service);
    $result = $extractor->extract('pdfs/test.pdf');

    expect($result->isUnavailable())->toBeTrue();
});

test('extract returns failed when startParsing errors', function () {
    $service = mock(LlamaParseService::class);
    $service->shouldReceive('isAvailable')->andReturnTrue();
    $service->shouldReceive('startParsing')
        ->with('pdfs/test.pdf')
        ->andReturn(['status' => 'error', 'error' => 'API connection failed']);

    $extractor = new LlamaParseExtractor($service);
    $result = $extractor->extract('pdfs/test.pdf');

    expect($result->isFailed())->toBeTrue();
    expect($result->errorMessage)->toBe('API connection failed');
});
