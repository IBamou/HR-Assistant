<?php

use App\DTOs\ExtractionResult;
use App\Services\Extraction\DoclingExtractor;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->testPdf = tempnam(sys_get_temp_dir(), 'docling_test_').'.pdf';
    file_put_contents($this->testPdf, 'fake PDF content');

    config()->set('services.docling.url', 'http://docling.test');
});

afterEach(function () {
    if (file_exists($this->testPdf)) {
        unlink($this->testPdf);
    }
});

test('isAvailable returns true when health check passes', function () {
    Http::fake([
        'http://docling.test/health' => Http::response(['status' => 'ok'], 200),
    ]);

    $extractor = new DoclingExtractor;

    expect($extractor->isAvailable())->toBeTrue();
});

test('isAvailable returns false when health check fails', function () {
    Http::fake([
        'http://docling.test/health' => Http::response([], 500),
    ]);

    $extractor = new DoclingExtractor;

    expect($extractor->isAvailable())->toBeFalse();
});

test('isAvailable returns false when connection fails', function () {
    Http::fake([
        'http://docling.test/health' => function () {
            throw new RuntimeException('Connection refused');
        },
    ]);

    $extractor = new DoclingExtractor;

    expect($extractor->isAvailable())->toBeFalse();
});

test('extract returns completed result on success', function () {
    Http::fake([
        'http://docling.test/parse' => Http::response([
            'success' => true,
            'content' => '# Extracted CV

John Doe

## Experience
Developer at ACME',
        ], 200),
    ]);

    $extractor = new DoclingExtractor;
    $result = $extractor->extract($this->testPdf);

    expect($result)->toBeInstanceOf(ExtractionResult::class);
    expect($result->isCompleted())->toBeTrue();
    expect($result->content)->toContain('Extracted CV');
    expect($result->extractorName)->toBe('DoclingExtractor');
});

test('extract returns unavailable on HTTP error', function () {
    Http::fake([
        'http://docling.test/parse' => Http::response([], 500),
    ]);

    $extractor = new DoclingExtractor;
    $result = $extractor->extract($this->testPdf);

    expect($result->isUnavailable())->toBeTrue();
    expect($result->errorMessage)->toContain('500');
});

test('extract returns failed on invalid response', function () {
    Http::fake([
        'http://docling.test/parse' => Http::response(['success' => false], 200),
    ]);

    $extractor = new DoclingExtractor;
    $result = $extractor->extract($this->testPdf);

    expect($result->isFailed())->toBeTrue();
});

test('extract returns unavailable on connection error', function () {
    Http::fake([
        'http://docling.test/parse' => function () {
            throw new RuntimeException('Connection refused');
        },
    ]);

    $extractor = new DoclingExtractor;
    $result = $extractor->extract($this->testPdf);

    expect($result->isUnavailable())->toBeTrue();
});
