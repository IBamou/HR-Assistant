<?php

namespace App\Ai\Agents;

use App\Prompts\CandidateInfoExtractorPrompt;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class CandidateInfoExtractor implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return (new CandidateInfoExtractorPrompt)->build();
    }
}
