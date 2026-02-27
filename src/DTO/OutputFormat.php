<?php

declare(strict_types=1);

namespace AiAdapter\DTO;

final class OutputFormat
{
    private function __construct(
        private readonly string $type,
        private readonly ?array $jsonSchema,
    ) {
    }

    public static function text(): self
    {
        return new self('text', null);
    }

    public static function jsonSchema(array $schema): self
    {
        return new self('json_schema', $schema);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isJsonSchema(): bool
    {
        return $this->type === 'json_schema';
    }

    public function schema(): ?array
    {
        return $this->jsonSchema;
    }
}
