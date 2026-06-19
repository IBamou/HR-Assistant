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
        $this->provider = config('ai.default', 'groq');
        $this->model = config('ai.model', 'meta-llama/llama-4-scout-17b-16e-instruct');
        $this->timeout = (int) config('ai.timeout', 120);
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

    public static function parseResponse(string $response): ?array
    {
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if (preg_match('/```json\s*(.*?)\s*```/s', $response, $m)) {
                $data = json_decode($m[1], true);
            } elseif (preg_match('/```\s*(.*?)\s*```/s', $response, $m)) {
                $data = json_decode($m[1], true);
            }
        }

        return is_array($data) ? $data : null;
    }
}
