<?php

use App\Prompts\CvScorerPrompt;

test('prompt contains expected JSON schema keys', function () {
    $prompt = (new CvScorerPrompt(
        offerTitle: 'Test Offer',
        offerDescription: 'Test description',
        requiredSkills: 'PHP, Laravel',
    ))->build();

    expect($prompt)->toContain('matching_score')
        ->and($prompt)->toContain('extracted_skills')
        ->and($prompt)->toContain('missing_skills')
        ->and($prompt)->toContain('strengths')
        ->and($prompt)->toContain('gaps')
        ->and($prompt)->toContain('justification')
        ->and($prompt)->toContain('recommendation');
});

test('prompt mentions offer context', function () {
    $prompt = (new CvScorerPrompt(
        offerTitle: 'Senior Developer',
        offerDescription: 'Looking for an experienced developer',
        requiredSkills: 'PHP, Laravel, MySQL',
    ))->build();

    expect($prompt)->toContain('Offer title')
        ->and($prompt)->toContain('Offer description')
        ->and($prompt)->toContain('Required skills');
});

test('prompt interpolates offer data', function () {
    $prompt = (new CvScorerPrompt(
        offerTitle: 'Senior PHP Developer',
        offerDescription: 'We need an expert Laravel developer',
        requiredSkills: 'PHP, Laravel, MySQL, Redis',
    ))->build();

    expect($prompt)->toContain('Senior PHP Developer')
        ->and($prompt)->toContain('We need an expert Laravel developer')
        ->and($prompt)->toContain('PHP, Laravel, MySQL, Redis');
});
