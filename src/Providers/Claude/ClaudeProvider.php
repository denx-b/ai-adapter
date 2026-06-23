<?php

declare(strict_types=1);

namespace AiAdapter\Providers\Claude;

use AiAdapter\Contracts\ProviderInterface;
use AiAdapter\Core\PromptBuilder;
use AiAdapter\Core\Transport\DefaultTransport;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\DTO\ChatResponse;
use AiAdapter\DTO\ResponseMeta;
use AiAdapter\DTO\Usage;
use AiAdapter\Exception\AuthException;
use AiAdapter\Exception\ModelNotFoundException;
use AiAdapter\Exception\ProviderUnavailableException;
use AiAdapter\Exception\RateLimitException;
use AiAdapter\Exception\TimeoutException;
use AiAdapter\Exception\ValidationException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ClaudeProvider implements ProviderInterface
{
    private readonly ClientInterface $http;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $defaultModel = 'claude-sonnet-4-6',
        private readonly string $baseUri = 'https://api.anthropic.com/v1',
        private readonly int $maxTokens = 4096,
        private readonly string $anthropicVersion = '2023-06-01',
    ) {
        $this->http = DefaultTransport::client();
        $this->requestFactory = DefaultTransport::requestFactory();
        $this->streamFactory = DefaultTransport::streamFactory();
    }

    public function name(): string
    {
        return 'claude';
    }

    public function defaultModel(): string
    {
        return $this->defaultModel;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $model = $request->modelName() ?? $this->defaultModel;
        $messages = PromptBuilder::build($request, true);

        $system = [];
        $conversation = [];
        foreach ($messages as $message) {
            if ($message->role() === 'system') {
                $system[] = $message->content();
                continue;
            }

            $conversation[] = [
                'role' => $message->role(),
                'content' => $message->content(),
            ];
        }

        if ($conversation === []) {
            throw new ValidationException('Claude provider requires at least one user or assistant message.');
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $this->maxTokens,
            'messages' => $conversation,
        ];

        if ($system !== []) {
            $payload['system'] = implode("\n\n", $system);
        }

        if ($request->temperatureValue() !== null) {
            $payload['temperature'] = $request->temperatureValue();
        }

        try {
            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new ValidationException('Failed to encode request payload.', 0, $exception);
        }

        $uri = rtrim($this->baseUri, '/') . '/messages';
        $httpRequest = $this->requestFactory->createRequest('POST', $uri)
            ->withHeader('x-api-key', $this->apiKey)
            ->withHeader('anthropic-version', $this->anthropicVersion)
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

        $text = $this->normalizeContent($data['content'] ?? []);

        $promptTokens = isset($data['usage']['input_tokens']) ? (int) $data['usage']['input_tokens'] : null;
        $completionTokens = isset($data['usage']['output_tokens']) ? (int) $data['usage']['output_tokens'] : null;
        $totalTokens = $promptTokens !== null && $completionTokens !== null
            ? $promptTokens + $completionTokens
            : null;

        $usage = new Usage($promptTokens, $completionTokens, $totalTokens);
        $meta = new ResponseMeta($this->name(), $model);

        return new ChatResponse($text, $usage, $meta, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function throwMappedException(int $status, array $data): never
    {
        $message = 'Claude provider error';
        if (isset($data['error']['message']) && is_string($data['error']['message'])) {
            $message = $data['error']['message'];
        } elseif (isset($data['message']) && is_string($data['message'])) {
            $message = $data['message'];
        }

        if ($status === 401 || $status === 403) {
            throw new AuthException($message);
        }

        if ($status === 404) {
            throw new ModelNotFoundException($message);
        }

        if ($status === 429) {
            throw new RateLimitException($message);
        }

        if ($status >= 500) {
            throw new ProviderUnavailableException($message);
        }

        throw new ValidationException($message);
    }

    /**
     * @param mixed $content
     */
    private function normalizeContent(mixed $content): string
    {
        if (!is_array($content)) {
            throw new ProviderUnavailableException('Response content has unsupported format.');
        }

        $parts = [];
        foreach ($content as $part) {
            if (is_array($part) && ($part['type'] ?? null) === 'text' && isset($part['text']) && is_string($part['text'])) {
                $parts[] = $part['text'];
            }
        }

        return implode("\n", $parts);
    }
}
