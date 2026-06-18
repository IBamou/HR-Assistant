<?php

namespace App\DTOs;

class ExtractionResult
{
    public const COMPLETED = 'completed';

    public const PENDING = 'pending';

    public const FAILED = 'failed';

    public const UNAVAILABLE = 'unavailable';

    public function __construct(
        public readonly string $status,
        public readonly string $content = '',
        public readonly string $extractorName = '',
        public readonly ?string $errorMessage = null,
    ) {}

    public static function completed(string $content, string $extractorName): self
    {
        return new self(self::COMPLETED, $content, $extractorName);
    }

    public static function pending(string $extractorName): self
    {
        return new self(self::PENDING, '', $extractorName);
    }

    public static function failed(string $extractorName, string $errorMessage): self
    {
        return new self(self::FAILED, '', $extractorName, $errorMessage);
    }

    public static function unavailable(string $extractorName): self
    {
        return new self(self::UNAVAILABLE, '', $extractorName);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::FAILED;
    }

    public function isUnavailable(): bool
    {
        return $this->status === self::UNAVAILABLE;
    }

    public function isEmpty(): bool
    {
        return $this->content === '';
    }
}
