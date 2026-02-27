<?php

declare(strict_types=1);

namespace AiAdapter\DTO;

use AiAdapter\Exception\ValidationException;

final class ChatResponse
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        private readonly string $text,
        private readonly Usage $usage,
        private readonly ResponseMeta $meta,
        private readonly array $raw = [],
    ) {
    }

    public function text(): string
    {
        return $this->text;
    }

    /**
     * @return array<string, mixed>
     */
    public function json(): array
    {
        if (isset($this->raw['json']) && is_array($this->raw['json'])) {
            return $this->raw['json'];
        }

        $decoded = json_decode($this->text, true);
        if (!is_array($decoded)) {
            throw new ValidationException('Response text is not valid JSON object.');
        }

        return $decoded;
    }

    public function usage(): Usage
    {
        return $this->usage;
    }

    public function meta(): ResponseMeta
    {
        return $this->meta;
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }

    public function withMeta(ResponseMeta $meta): self
    {
        return new self($this->text, $this->usage, $meta, $this->raw);
    }
}
