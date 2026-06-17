<?php

namespace App\Services;

use Laravel\Ai\Contracts\Agent;

class AiClient
{
    private readonly string $provider;

    private readonly string $model;

    private readonly int $timeout;

    public function __construct()
    {
        $this->provider = config('ai.default');
        $this->model = config('ai.model', 'meta-llama/llama-4-scout-17b-16e-instruct');
        $this->timeout = config('ai.timeout', 120);
    }

    public function prompt(Agent $agent, string $prompt): string
    {
        $response = $agent->prompt(
            prompt: $prompt,
            provider: $this->provider,
            model: $this->model,
            timeout: $this->timeout,
        );

        return (string) $response;
    }
}
