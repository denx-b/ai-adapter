<?php

declare(strict_types=1);

namespace AiAdapter\Contracts;

use AiAdapter\Core\ProviderRegistry;
use AiAdapter\Core\RouteTarget;
use AiAdapter\DTO\ChatRequest;
use Throwable;

interface RouterInterface
{
    /**
     * @return list<RouteTarget>
     */
    public function resolve(ChatRequest $request, ProviderRegistry $providers): array;

    public function canFallback(Throwable $exception): bool;
}
