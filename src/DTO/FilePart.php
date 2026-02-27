<?php

declare(strict_types=1);

namespace AiAdapter\DTO;

use AiAdapter\Exception\ValidationException;

final class FilePart
{
    private function __construct(
        private readonly string $name,
        private readonly ?string $path,
        private readonly ?string $content,
    ) {
    }

    public static function fromPath(string $path, ?string $name = null): self
    {
        if ($path === '') {
            throw new ValidationException('File path cannot be empty.');
        }

        return new self($name ?? basename($path), $path, null);
    }

    public static function fromString(string $content, string $name = 'inline.txt'): self
    {
        return new self($name, null, $content);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function content(): string
    {
        if ($this->content !== null) {
            return $this->content;
        }

        if ($this->path === null || !is_file($this->path) || !is_readable($this->path)) {
            throw new ValidationException('File is not readable: ' . ($this->path ?? 'unknown'));
        }

        $content = file_get_contents($this->path);
        if ($content === false) {
            throw new ValidationException('Failed to read file: ' . $this->path);
        }

        return $content;
    }
}
