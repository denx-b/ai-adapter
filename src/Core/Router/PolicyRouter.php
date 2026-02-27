<?php

declare(strict_types=1);

namespace AiAdapter\Core\Router;

use AiAdapter\Contracts\RouterInterface;
use AiAdapter\Core\ProviderRegistry;
use AiAdapter\Core\RouteTarget;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\Exception\ValidationException;
use Throwable;

final class PolicyRouter implements RouterInterface
{
    /**
     * @var callable(ChatRequest): string|list<string>
     */
    private $policy;

    /**
     * @param callable(ChatRequest): string|list<string> $policy
     */
    public function __construct(callable $policy)
    {
        $this->policy = $policy;
    }

    /**
     * @return list<RouteTarget>
     */
    public function resolve(ChatRequest $request, ProviderRegistry $providers): array
    {
        $explicitProvider = $request->providerName();
        $explicitModel = $request->modelName();

        if ($explicitProvider !== null && $explicitModel !== null) {
            return [new RouteTarget($explicitProvider, $explicitModel)];
        }

        if ($explicitProvider !== null && $explicitModel === null) {
            $provider = $providers->get($explicitProvider);
            return [new RouteTarget($provider->name(), $provider->defaultModel())];
        }

        $decision = ($this->policy)($request);
        $targets = is_array($decision) ? $decision : [$decision];
        if ($targets === []) {
            throw new ValidationException('Policy router returned no targets.');
        }

        return array_map(static fn (string $target): RouteTarget => RouteTarget::fromString($target), $targets);
    }

    public function canFallback(Throwable $exception): bool
    {
        return false;
    }
}
