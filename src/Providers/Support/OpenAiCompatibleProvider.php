<?php

declare(strict_types=1);

namespace AiAdapter\Providers\Support;

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

abstract class OpenAiCompatibleProvider implements ProviderInterface
{
    private readonly ClientInterface $http;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUri,
        private readonly string $defaultModel,
    ) {
        $this->http = DefaultTransport::client();
        $this->requestFactory = DefaultTransport::requestFactory();
        $this->streamFactory = DefaultTransport::streamFactory();
    }

    public function defaultModel(): string
    {
        return $this->defaultModel;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $model = $request->modelName() ?? $this->defaultModel();

        $payload = [
            'model' => $model,
            'messages' => array_map(
                static fn ($message): array => [
                    'role' => $message->role(),
                    'content' => $message->content(),
                ],
                PromptBuilder::build($request)
            ),
        ];

        if ($request->temperatureValue() !== null) {
            $payload['temperature'] = $request->temperatureValue();
        }

        if ($request->outputFormat()?->isJsonSchema()) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'response',
                    'schema' => $request->outputFormat()?->schema(),
                ],
            ];
        }

        try {
            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new ValidationException('Failed to encode request payload.', 0, $exception);
        }

        $uri = rtrim($this->baseUri, '/') . '/chat/completions';
        $httpRequest = $this->requestFactory->createRequest('POST', $uri)
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey)
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

        $choice = $data['choices'][0]['message']['content'] ?? '';
        $text = $this->normalizeContent($choice);

        $usage = new Usage(
            isset($data['usage']['prompt_tokens']) ? (int) $data['usage']['prompt_tokens'] : null,
            isset($data['usage']['completion_tokens']) ? (int) $data['usage']['completion_tokens'] : null,
            isset($data['usage']['total_tokens']) ? (int) $data['usage']['total_tokens'] : null,
        );

        $meta = new ResponseMeta($this->name(), $model);

        return new ChatResponse($text, $usage, $meta, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function throwMappedException(int $status, array $data): never
    {
        $message = 'Provider error';
        if (isset($data['error']['message']) && is_string($data['error']['message'])) {
            $message = $data['error']['message'];
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
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            $parts = [];
            foreach ($content as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    $parts[] = $part['text'];
                }
            }
            return implode("\n", $parts);
        }

        throw new ProviderUnavailableException('Response content has unsupported format.');
    }
}
