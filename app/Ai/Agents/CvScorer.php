<?php

namespace App\Ai\Agents;

use App\Prompts\CvScorerPrompt;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class CvScorer implements Agent
{
    use Promptable;

    public function __construct(
        private readonly string $offerTitle,
        private readonly string $offerDescription,
        private readonly string $requiredSkills,
    ) {}

    public function instructions(): string
    {
        return (new CvScorerPrompt(
            offerTitle: $this->offerTitle,
            offerDescription: $this->offerDescription,
            requiredSkills: $this->requiredSkills,
        ))->build();
    }
}
