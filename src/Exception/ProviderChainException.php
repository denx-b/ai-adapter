<?php

declare(strict_types=1);

namespace AiAdapter\Exception;

use Throwable;

final class ProviderChainException extends ProviderUnavailableException
{
    /**
     * @param list<array<string, mixed>> $attempts
     */
    public function __construct(
        string $message,
        private readonly array $attempts,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function attempts(): array
    {
        return $this->attempts;
    }
}
