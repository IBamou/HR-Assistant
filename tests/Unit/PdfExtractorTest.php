<?php

use App\Services\Extraction\LocalPdfExtractor;

test('local pdf extractor can be instantiated', function () {
    $extractor = new LocalPdfExtractor;

    expect($extractor)->toBeInstanceOf(LocalPdfExtractor::class);
});

test('local pdf extractor returns failed for non-existent file', function () {
    $extractor = new LocalPdfExtractor;
    $result = $extractor->extract('/nonexistent/file.pdf');

    expect($result->isFailed())->toBeTrue();
});
