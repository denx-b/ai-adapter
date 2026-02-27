<?php

declare(strict_types=1);

namespace AiAdapter\Tests\Support;

use AiAdapter\Contracts\ProviderInterface;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\DTO\ChatResponse;
use Closure;

final class FakeProvider implements ProviderInterface
{
    /**
     * @var list<ChatRequest>
     */
    private array $received = [];

    /**
     * @param Closure(ChatRequest): ChatResponse $handler
     */
    public function __construct(
        private readonly string $name,
        private readonly string $defaultModel,
        private readonly Closure $handler,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function defaultModel(): string
    {
        return $this->defaultModel;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $this->received[] = $request;

        return ($this->handler)($request);
    }

    public function calls(): int
    {
        return count($this->received);
    }

    public function lastRequest(): ?ChatRequest
    {
        if ($this->received === []) {
            return null;
        }

        return $this->received[array_key_last($this->received)];
    }
}
