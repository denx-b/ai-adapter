<?php

declare(strict_types=1);

namespace AiAdapter\DTO;

final class Usage
{
    public function __construct(
        private readonly ?int $promptTokens,
        private readonly ?int $completionTokens,
        private readonly ?int $totalTokens,
    ) {
    }

    public static function empty(): self
    {
        return new self(null, null, null);
    }

    public function promptTokens(): ?int
    {
        return $this->promptTokens;
    }

    public function completionTokens(): ?int
    {
        return $this->completionTokens;
    }

    public function totalTokens(): ?int
    {
        return $this->totalTokens;
    }
}
