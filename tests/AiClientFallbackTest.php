<?php

declare(strict_types=1);

namespace AiAdapter\Tests;

use AiAdapter\Ai;
use AiAdapter\Core\Router;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\Exception\AuthException;
use AiAdapter\Exception\RateLimitException;
use AiAdapter\Exception\ValidationException;
use AiAdapter\Tests\Support\FakeProvider;
use AiAdapter\Tests\Support\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class AiClientFallbackTest extends TestCase
{
    public function testFallbackToSecondProviderOnRateLimit(): void
    {
        $primary = new FakeProvider(
            'primary',
            'p-model',
            static fn () => throw new RateLimitException('Too many requests'),
        );

        $secondary = new FakeProvider(
            'secondary',
            's-model',
            static fn (ChatRequest $request) => ResponseFactory::text(
                'ok from secondary',
                'secondary',
                $request->modelName() ?? 's-model',
            ),
        );

        $client = Ai::make()
            ->register($primary)
            ->register($secondary)
            ->router(Router::fallback([
                'primary:p-model',
                'secondary:s-model',
            ]));

        $response = $client->chat(
            ChatRequest::make()->system('You are concise')->user('Hello')
        );

        self::assertSame('ok from secondary', $response->text());
        self::assertSame('secondary', $response->meta()->provider());
        self::assertSame('s-model', $response->meta()->model());
        self::assertCount(1, $response->meta()->attempts());
        self::assertSame('primary', $response->meta()->attempts()[0]['provider']);
    }

    public function testFallbackOnAuthException(): void
    {
        $primary = new FakeProvider(
            'primary',
            'p-model',
            static fn () => throw new AuthException('Bad API key'),
        );

        $secondary = new FakeProvider(
            'secondary',
            's-model',
            static fn () => ResponseFactory::text('ok from secondary', 'secondary', 's-model'),
        );

        $client = Ai::make()
            ->register($primary)
            ->register($secondary)
            ->router(Router::fallback([
                'primary:p-model',
                'secondary:s-model',
            ]));

        $response = $client->chat(ChatRequest::make()->user('Hello'));

        self::assertSame('ok from secondary', $response->text());
        self::assertSame(1, $secondary->calls());
    }

    public function testDoesNotFallbackOnValidationException(): void
    {
        $primary = new FakeProvider(
            'primary',
            'p-model',
            static fn () => throw new ValidationException('Bad request payload'),
        );

        $secondary = new FakeProvider(
            'secondary',
            's-model',
            static fn () => ResponseFactory::text('must not be called', 'secondary', 's-model'),
        );

        $client = Ai::make()
            ->register($primary)
            ->register($secondary)
            ->router(Router::fallback([
                'primary:p-model',
                'secondary:s-model',
            ]));

        try {
            $client->chat(ChatRequest::make()->user('Hello'));
            self::fail('Expected ProviderChainException was not thrown.');
        } catch (\AiAdapter\Exception\ProviderChainException $exception) {
            self::assertCount(1, $exception->attempts());
            self::assertSame('primary', $exception->attempts()[0]['provider']);
            self::assertSame(0, $secondary->calls());
        }
    }

    public function testExplicitTargetOverridesRouterTargets(): void
    {
        $first = new FakeProvider(
            'first',
            'f-model',
            static fn () => ResponseFactory::text('must not be called', 'first', 'f-model'),
        );

        $second = new FakeProvider(
            'second',
            's-model',
            static fn (ChatRequest $request) => ResponseFactory::text(
                'ok from second',
                'second',
                $request->modelName() ?? 's-model',
            ),
        );

        $client = Ai::make()
            ->register($first)
            ->register($second)
            ->router(Router::fallback([
                'first:f-model',
                'second:s-model',
            ]));

        $response = $client->chat(
            ChatRequest::make()
                ->target('second', 's-model-explicit')
                ->user('Run explicit provider/model')
        );

        self::assertSame(0, $first->calls());
        self::assertSame(1, $second->calls());
        self::assertSame('s-model-explicit', $second->lastRequest()?->modelName());
        self::assertSame('second', $response->meta()->provider());
        self::assertSame('s-model-explicit', $response->meta()->model());
    }
}
