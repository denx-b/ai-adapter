<?php

declare(strict_types=1);

namespace AiAdapter\DTO;

final class ResponseMeta
{
    /**
     * @param list<array<string, mixed>> $attempts
     */
    public function __construct(
        private readonly ?string $provider,
        private readonly ?string $model,
        private readonly ?int $latencyMs = null,
        private readonly array $attempts = [],
    ) {
    }

    public function provider(): ?string
    {
        return $this->provider;
    }

    public function model(): ?string
    {
        return $this->model;
    }

    public function latencyMs(): ?int
    {
        return $this->latencyMs;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function attempts(): array
    {
        return $this->attempts;
    }

    /**
     * @param list<array<string, mixed>> $attempts
     */
    public function withAttempts(array $attempts): self
    {
        return new self($this->provider, $this->model, $this->latencyMs, $attempts);
    }

    public function withProviderAndModel(string $provider, string $model): self
    {
        return new self($provider, $model, $this->latencyMs, $this->attempts);
    }

    public function withLatencyMs(int $latencyMs): self
    {
        return new self($this->provider, $this->model, $latencyMs, $this->attempts);
    }
}
