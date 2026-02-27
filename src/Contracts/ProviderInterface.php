<?php

declare(strict_types=1);

namespace AiAdapter\Contracts;

use AiAdapter\DTO\ChatRequest;
use AiAdapter\DTO\ChatResponse;

interface ProviderInterface
{
    public function name(): string;

    public function defaultModel(): string;

    public function chat(ChatRequest $request): ChatResponse;
}
