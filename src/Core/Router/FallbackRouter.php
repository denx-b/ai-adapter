<?php

declare(strict_types=1);

namespace AiAdapter\Core\Router;

use AiAdapter\Contracts\RouterInterface;
use AiAdapter\Core\ProviderRegistry;
use AiAdapter\Core\RouteTarget;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\Exception\AuthException;
use AiAdapter\Exception\TimeoutException;
use AiAdapter\Exception\RateLimitException;
use AiAdapter\Exception\ProviderUnavailableException;
use AiAdapter\Exception\ValidationException;
use Throwable;

final class FallbackRouter implements RouterInterface
{
    /**
     * @var list<string> provider or provider:model
     */
    private array $targets;

    /**
     * @var list<class-string<Throwable>>
     */
    private array $fallbackOn = [
        AuthException::class,
        TimeoutException::class,
        RateLimitException::class,
        ProviderUnavailableException::class,
    ];

    /**
     * @param list<string> $targets provider or provider:model
     */
    public function __construct(array $targets)
    {
        $this->targets = $targets;
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
            $resolvedTargets = [];
            foreach ($this->targets as $target) {
                $target = trim($target);
                if ($target === '') {
                    throw new ValidationException('Fallback target cannot be empty.');
                }

                if (str_contains($target, ':')) {
                    $resolvedTargets[] = RouteTarget::fromString($target);
                    continue;
                }

                $provider = $providers->get($target);
                $resolvedTargets[] = new RouteTarget($provider->name(), $provider->defaultModel());
            }

            return $resolvedTargets;
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
