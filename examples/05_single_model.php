<?php

require __DIR__ . '/../vendor/autoload.php';

\Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->safeLoad();

$response = \AiAdapter\Ai::make()
    ->withClaude((string) getenv('CLAUDE_API_KEY'))
    ->chat(
        \AiAdapter\DTO\ChatRequest::make()
            ->user('Объясни простыми словами, зачем нужен fallback между несколькими AI-провайдерами.')
    );

echo $response->text() . PHP_EOL;
