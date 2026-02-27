<?php

declare(strict_types=1);

namespace AiAdapter\Core;

use AiAdapter\Contracts\AiClientInterface;
use AiAdapter\Contracts\ProviderInterface;
use AiAdapter\Contracts\RouterInterface;
use AiAdapter\Core\Router\FallbackRouter;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\DTO\ChatResponse;
use AiAdapter\Exception\ProviderChainException;
use AiAdapter\Providers\DeepSeek\DeepSeekProvider;
use AiAdapter\Providers\OpenAI\OpenAiProvider;
use AiAdapter\Providers\Yandex\YandexProvider;
use Throwable;

final class AiClient implements AiClientInterface
{
    private ProviderRegistry $providers;

    private RouterInterface $router;

    public function __construct()
    {
        $this->providers = new ProviderRegistry();
        $this->router = new FallbackRouter([]);
    }

    public function register(ProviderInterface $provider): self
    {
        $this->providers->register($provider);

        return $this;
    }

    public function withOpenAi(
        string $apiKey,
        string $defaultModel = 'gpt-4.1-mini',
        string $baseUri = 'https://api.openai.com/v1',
    ): self {
        return $this->register(new OpenAiProvider($apiKey, $defaultModel, $baseUri));
    }

    public function withYandex(
        string $apiKey,
        string $folderId,
        string $defaultModel = 'yandexgpt-lite',
        string $endpoint = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion',
    ): self {
        return $this->register(new YandexProvider($apiKey, $folderId, $defaultModel, $endpoint));
    }

    public function withDeepSeek(
        string $apiKey,
        string $defaultModel = 'deepseek-chat',
        string $baseUri = 'https://api.deepseek.com/v1',
    ): self {
        return $this->register(new DeepSeekProvider($apiKey, $defaultModel, $baseUri));
    }

    public function router(RouterInterface $router): self
    {
        $this->router = $router;

        return $this;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $targets = $this->router->resolve($request, $this->providers);

        $attempts = [];
        $lastException = null;

        foreach ($targets as $index => $target) {
            $provider = $this->providers->get($target->provider());
            $routedRequest = $request->target($target->provider(), $target->model());

            $started = microtime(true);
            try {
                $response = $provider->chat($routedRequest);

                $latencyMs = (int) round((microtime(true) - $started) * 1000);
                $meta = $response->meta()
                    ->withProviderAndModel($target->provider(), $target->model())
                    ->withLatencyMs($latencyMs)
                    ->withAttempts($attempts);

                return $response->withMeta($meta);
            } catch (Throwable $exception) {
                $attempts[] = [
                    'provider' => $target->provider(),
                    'model' => $target->model(),
                    'error' => $exception::class,
                    'message' => $exception->getMessage(),
                ];
                $lastException = $exception;

                $isLastTarget = $index === count($targets) - 1;
                if ($isLastTarget || !$this->router->canFallback($exception)) {
                    throw new ProviderChainException(
                        'All provider attempts failed.',
                        $attempts,
                        $lastException,
                    );
                }
            }
        }

        throw new ProviderChainException('No providers were attempted.', $attempts, $lastException);
    }
}
