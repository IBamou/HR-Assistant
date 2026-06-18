<?php

use App\DTOs\ExtractionResult;
use App\Services\Extraction\Contracts\Extractor;
use App\Services\Extraction\ExtractionOrchestrator;

test('returns first successful extraction', function () {
    $first = mock(Extractor::class);
    $first->shouldReceive('isAvailable')->andReturnTrue();
    $first->shouldReceive('extract')
        ->with('test.pdf')
        ->andReturn(ExtractionResult::completed('First result', 'FirstExtractor'));

    $second = mock(Extractor::class);
    $second->shouldNotReceive('isAvailable');
    $second->shouldNotReceive('extract');

    $orchestrator = new ExtractionOrchestrator($first, $second);
    $result = $orchestrator->extract('test.pdf');

    expect($result->isCompleted())->toBeTrue();
    expect($result->content)->toBe('First result');
    expect($result->extractorName)->toBe('FirstExtractor');
});

test('skips unavailable extractors', function () {
    $first = mock(Extractor::class);
    $first->shouldReceive('isAvailable')->andReturnFalse();
    $first->shouldNotReceive('extract');

    $second = mock(Extractor::class);
    $second->shouldReceive('isAvailable')->andReturnTrue();
    $second->shouldReceive('extract')
        ->with('test.pdf')
        ->andReturn(ExtractionResult::completed('Second result', 'SecondExtractor'));

    $orchestrator = new ExtractionOrchestrator($first, $second);
    $result = $orchestrator->extract('test.pdf');

    expect($result->isCompleted())->toBeTrue();
    expect($result->content)->toBe('Second result');
});

test('stops chain on pending', function () {
    $first = mock(Extractor::class);
    $first->shouldReceive('isAvailable')->andReturnTrue();
    $first->shouldReceive('extract')
        ->with('test.pdf')
        ->andReturn(ExtractionResult::pending('FirstExtractor'));

    $second = mock(Extractor::class);
    $second->shouldNotReceive('isAvailable');
    $second->shouldNotReceive('extract');

    $orchestrator = new ExtractionOrchestrator($first, $second);
    $result = $orchestrator->extract('test.pdf');

    expect($result->isPending())->toBeTrue();
});

test('tries next on failure', function () {
    $first = mock(Extractor::class);
    $first->shouldReceive('isAvailable')->andReturnTrue();
    $first->shouldReceive('extract')
        ->with('test.pdf')
        ->andReturn(ExtractionResult::failed('FirstExtractor', 'Error'));

    $second = mock(Extractor::class);
    $second->shouldReceive('isAvailable')->andReturnTrue();
    $second->shouldReceive('extract')
        ->with('test.pdf')
        ->andReturn(ExtractionResult::completed('Second result', 'SecondExtractor'));

    $orchestrator = new ExtractionOrchestrator($first, $second);
    $result = $orchestrator->extract('test.pdf');

    expect($result->isCompleted())->toBeTrue();
    expect($result->content)->toBe('Second result');
});

test('returns failed when all extractors fail', function () {
    $first = mock(Extractor::class);
    $first->shouldReceive('isAvailable')->andReturnTrue();
    $first->shouldReceive('extract')
        ->with('test.pdf')
        ->andReturn(ExtractionResult::failed('FirstExtractor', 'Error 1'));

    $second = mock(Extractor::class);
    $second->shouldReceive('isAvailable')->andReturnTrue();
    $second->shouldReceive('extract')
        ->with('test.pdf')
        ->andReturn(ExtractionResult::failed('SecondExtractor', 'Error 2'));

    $orchestrator = new ExtractionOrchestrator($first, $second);
    $result = $orchestrator->extract('test.pdf');

    expect($result->isFailed())->toBeTrue();
    expect($result->errorMessage)->toContain('All extractors failed');
});

test('returns failed when all extractors are unavailable', function () {
    $first = mock(Extractor::class);
    $first->shouldReceive('isAvailable')->andReturnFalse();
    $first->shouldNotReceive('extract');

    $second = mock(Extractor::class);
    $second->shouldReceive('isAvailable')->andReturnFalse();
    $second->shouldNotReceive('extract');

    $orchestrator = new ExtractionOrchestrator($first, $second);
    $result = $orchestrator->extract('test.pdf');

    expect($result->isFailed())->toBeTrue();
});
