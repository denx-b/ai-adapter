<?php

declare(strict_types=1);

namespace AiAdapter\Core;

use AiAdapter\Core\Router\FallbackRouter;
use AiAdapter\Core\Router\PolicyRouter;

final class Router
{
    /**
     * @param list<string> $targets provider:model
     */
    public static function fallback(array $targets): FallbackRouter
    {
        return new FallbackRouter($targets);
    }

    /**
     * @param callable(\AiAdapter\DTO\ChatRequest): string|list<string> $policy
     */
    public static function policy(callable $policy): PolicyRouter
    {
        return new PolicyRouter($policy);
    }
}
