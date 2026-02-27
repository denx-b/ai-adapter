<?php

declare(strict_types=1);

namespace AiAdapter\Providers\Yandex;

use AiAdapter\Contracts\ProviderInterface;
use AiAdapter\Core\PromptBuilder;
use AiAdapter\Core\Transport\DefaultTransport;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\DTO\ChatResponse;
use AiAdapter\DTO\ResponseMeta;
use AiAdapter\DTO\Usage;
use AiAdapter\Exception\AuthException;
use AiAdapter\Exception\ProviderUnavailableException;
use AiAdapter\Exception\RateLimitException;
use AiAdapter\Exception\TimeoutException;
use AiAdapter\Exception\ValidationException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class YandexProvider implements ProviderInterface
{
    private readonly ClientInterface $http;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $folderId,
        private readonly string $defaultModel = 'yandexgpt-lite',
        private readonly string $endpoint = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion',
    ) {
        $this->http = DefaultTransport::client();
        $this->requestFactory = DefaultTransport::requestFactory();
        $this->streamFactory = DefaultTransport::streamFactory();
    }

    public function name(): string
    {
        return 'yandex';
    }

    public function defaultModel(): string
    {
        return $this->defaultModel;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $model = $request->modelName() ?? $this->defaultModel;

        $payload = [
            'modelUri' => sprintf('gpt://%s/%s/latest', $this->folderId, $model),
            'completionOptions' => [
                'stream' => false,
                'temperature' => $request->temperatureValue() ?? 0.2,
            ],
            'messages' => array_map(
                static fn ($message): array => [
                    'role' => $message->role(),
                    'text' => $message->content(),
                ],
                PromptBuilder::build($request, true)
            ),
        ];

        try {
            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new ValidationException('Failed to encode request payload.', 0, $exception);
        }

        $httpRequest = $this->requestFactory->createRequest('POST', $this->endpoint)
            ->withHeader('Authorization', 'Api-Key ' . $this->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body));

        try {
            $httpResponse = $this->http->sendRequest($httpRequest);
        } catch (ClientExceptionInterface $exception) {
            $message = strtolower($exception->getMessage());
            if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
                throw new TimeoutException($exception->getMessage(), 0, $exception);
            }
            throw new ProviderUnavailableException($exception->getMessage(), 0, $exception);
        }

        $status = $httpResponse->getStatusCode();
        $raw = (string) $httpResponse->getBody();

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new ProviderUnavailableException('Provider returned non-JSON response.');
        }

        if ($status >= 400) {
            $this->throwMappedException($status, $data);
        }

        $text = (string) ($data['result']['alternatives'][0]['message']['text'] ?? '');

        $usage = new Usage(
            isset($data['result']['usage']['inputTextTokens']) ? (int) $data['result']['usage']['inputTextTokens'] : null,
            isset($data['result']['usage']['completionTokens']) ? (int) $data['result']['usage']['completionTokens'] : null,
            isset($data['result']['usage']['totalTokens']) ? (int) $data['result']['usage']['totalTokens'] : null,
        );

        $meta = new ResponseMeta($this->name(), $model);

        return new ChatResponse($text, $usage, $meta, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function throwMappedException(int $status, array $data): never
    {
        $message = 'Yandex provider error';
        if (isset($data['message']) && is_string($data['message'])) {
            $message = $data['message'];
        }

        if ($status === 401 || $status === 403) {
            throw new AuthException($message);
        }

        if ($status === 429) {
            throw new RateLimitException($message);
        }

        if ($status >= 500) {
            throw new ProviderUnavailableException($message);
        }

        throw new ValidationException($message);
    }
}
