<?php

use App\DTOs\ExtractionResult;
use App\Services\Extraction\LocalPdfExtractor;

test('isAvailable always returns true', function () {
    $extractor = new LocalPdfExtractor;

    expect($extractor->isAvailable())->toBeTrue();
});

test('extract returns completed with text from PDF', function () {
    $pdfPath = __DIR__.'/../../Fixtures/sample.pdf';

    if (! file_exists($pdfPath)) {
        $this->markTestSkipped('Sample PDF fixture not found at '.$pdfPath);
    }

    $extractor = new LocalPdfExtractor;
    $result = $extractor->extract($pdfPath);

    expect($result)->toBeInstanceOf(ExtractionResult::class);
    expect($result->isCompleted())->toBeTrue();
    expect($result->extractorName)->toBe('LocalPdfExtractor');
});

test('extract returns failed for non-existent file', function () {
    $extractor = new LocalPdfExtractor;
    $result = $extractor->extract('/tmp/nonexistent_'.uniqid().'.pdf');

    expect($result->isFailed())->toBeTrue();
});
