<?php

declare(strict_types=1);

namespace AiAdapter\DTO;

use AiAdapter\Exception\ValidationException;

final class Message
{
    private const ALLOWED_ROLES = ['system', 'user', 'assistant'];

    public function __construct(
        private readonly string $role,
        private readonly string $content,
    ) {
        if (!in_array($this->role, self::ALLOWED_ROLES, true)) {
            throw new ValidationException('Unsupported message role: ' . $this->role);
        }
    }

    public static function system(string $content): self
    {
        return new self('system', $content);
    }

    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    public static function assistant(string $content): self
    {
        return new self('assistant', $content);
    }

    public function role(): string
    {
        return $this->role;
    }

    public function content(): string
    {
        return $this->content;
    }
}
