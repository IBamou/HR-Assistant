<?php

use App\DTOs\ExtractionResult;
use App\Jobs\ExtractCandidateInfoJob;
use App\Services\Extraction\ExtractionOrchestrator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::put('pdfs/test.pdf', 'fake PDF content');

    $this->cacheKey = 'cv_extraction_test_'.md5('pdfs/test.pdf');
    $this->cvPath = 'pdfs/test.pdf';
});

test('stores error in cache when orchestrator fails', function () {
    $orchestrator = mock(ExtractionOrchestrator::class);
    $orchestrator->shouldReceive('extract')
        ->with($this->cvPath)
        ->andReturn(ExtractionResult::failed('TestExtractor', 'Something went wrong'));

    app()->instance(ExtractionOrchestrator::class, $orchestrator);

    $job = new ExtractCandidateInfoJob($this->cvPath, $this->cacheKey);
    $job->tries = 1;

    $job->handle();

    $cached = Cache::get($this->cacheKey);

    expect($cached['status'])->toBe('error');
    expect($cached['message'])->toBe('Could not extract candidate information. Please enter the details manually.');
});

test('stores warning when orchestrator returns empty', function () {
    $orchestrator = mock(ExtractionOrchestrator::class);
    $orchestrator->shouldReceive('extract')
        ->with($this->cvPath)
        ->andReturn(ExtractionResult::completed('', 'TestExtractor'));

    app()->instance(ExtractionOrchestrator::class, $orchestrator);

    $job = new ExtractCandidateInfoJob($this->cvPath, $this->cacheKey);
    $job->tries = 1;

    $job->handle();

    $cached = Cache::get($this->cacheKey);

    expect($cached['status'])->toBe('warning');
});

test('releases job when orchestrator returns pending', function () {
    $orchestrator = mock(ExtractionOrchestrator::class);
    $orchestrator->shouldReceive('extract')
        ->with($this->cvPath)
        ->andReturn(ExtractionResult::pending('TestExtractor'));

    app()->instance(ExtractionOrchestrator::class, $orchestrator);

    $job = new ExtractCandidateInfoJob($this->cvPath, $this->cacheKey);
    $job->tries = 1;

    $job->handle();
});
