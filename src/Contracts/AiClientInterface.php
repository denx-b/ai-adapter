<?php

declare(strict_types=1);

namespace AiAdapter\Contracts;

use AiAdapter\DTO\ChatRequest;
use AiAdapter\DTO\ChatResponse;

interface AiClientInterface
{
    public function chat(ChatRequest $request): ChatResponse;
}
