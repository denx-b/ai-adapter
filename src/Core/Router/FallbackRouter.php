<?php

declare(strict_types=1);

namespace AiAdapter\Core\Router;

use AiAdapter\Contracts\RouterInterface;
use AiAdapter\Core\ProviderRegistry;
use AiAdapter\Core\RouteTarget;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\Exception\TimeoutException;
use AiAdapter\Exception\RateLimitException;
use AiAdapter\Exception\ProviderUnavailableException;
use AiAdapter\Exception\ValidationException;
use Throwable;

final class FallbackRouter implements RouterInterface
{
    /**
     * @var list<RouteTarget>
     */
    private array $targets;

    /**
     * @var list<class-string<Throwable>>
     */
    private array $fallbackOn = [
        TimeoutException::class,
        RateLimitException::class,
        ProviderUnavailableException::class,
    ];

    /**
     * @param list<string> $targets
     */
    public function __construct(array $targets)
    {
        $this->targets = array_map(static fn (string $target): RouteTarget => RouteTarget::fromString($target), $targets);
    }

    /**
     * @param list<class-string<Throwable>> $exceptions
     */
    public function on(array $exceptions): self
    {
        $clone = clone $this;
        $clone->fallbackOn = $exceptions;

        return $clone;
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

        if ($this->targets !== []) {
            return $this->targets;
        }

        if ($providers->count() === 1) {
            $provider = array_values($providers->all())[0];
            return [new RouteTarget($provider->name(), $provider->defaultModel())];
        }

        throw new ValidationException(
            'No route resolved. Set request target provider/model, or configure Router::fallback([...]).'
        );
    }

    public function canFallback(Throwable $exception): bool
    {
        foreach ($this->fallbackOn as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
