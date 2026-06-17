<?php

use App\Services\Extraction\PdfExtractor;

test('pdf extractor can be instantiated', function () {
    $extractor = new PdfExtractor;

    expect($extractor)->toBeInstanceOf(PdfExtractor::class);
});

test('pdf extractor throws exception for non-existent file', function () {
    $extractor = new PdfExtractor;

    $extractor->extract('/nonexistent/file.pdf');
})->throws(Exception::class);
