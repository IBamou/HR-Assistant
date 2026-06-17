<?php

use App\Jobs\ExtractCandidateInfoJob;
use App\Services\Extraction\LlamaParseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::put('pdfs/test.pdf', 'fake PDF content');

    $this->cacheKey = 'cv_extraction_test_'.md5('pdfs/test.pdf');
    $this->cvPath = 'pdfs/test.pdf';
});

test('stores error in cache when LlamaParse start throws', function () {
    $llamaMock = mock(LlamaParseService::class);
    $llamaMock->shouldReceive('isAvailable')->andReturn(true);
    $llamaMock->shouldReceive('startParsing')
        ->with($this->cvPath)
        ->andThrow(new RuntimeException('Connection failed'));

    app()->instance(LlamaParseService::class, $llamaMock);

    $job = new ExtractCandidateInfoJob($this->cvPath, $this->cacheKey);
    $job->tries = 1;

    $job->handle();

    $cached = Cache::get($this->cacheKey);

    expect($cached['status'])->toBe('error');
    expect($cached['message'])->toBe('Could not extract candidate information. Please enter the details manually.');
});

test('falls back to PdfExtractor when LlamaParse unavailable', function () {
    $llamaMock = mock(LlamaParseService::class);
    $llamaMock->shouldReceive('isAvailable')->andReturn(false);

    app()->instance(LlamaParseService::class, $llamaMock);

    $job = new ExtractCandidateInfoJob($this->cvPath, $this->cacheKey);
    $job->tries = 1;

    $job->handle();
});
