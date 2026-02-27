# ai-adapter

Provider-agnostic PHP library for chat-style LLM requests.

## MVP scope

- PHP `^8.2`
- PSR transport under the hood (`Guzzle` + `Nyholm PSR-7`)
- Chat API only
- Providers: OpenAI, YandexGPT, DeepSeek
- Router fallback policy (timeout/429/5xx)
- Optional structured output with JSON schema

## Install

```bash
composer require denx-b/ai-adapter
```

## Quick start

```php
<?php

declare(strict_types=1);

use AiAdapter\Ai;
use AiAdapter\Core\Router;
use AiAdapter\DTO\ChatRequest;

$openAiApiKey = getenv('OPENAI_API_KEY') ?: '';
$yandexApiKey = getenv('YANDEX_API_KEY') ?: '';
$yandexFolderId = getenv('YANDEX_FOLDER_ID') ?: '';
$deepSeekApiKey = getenv('DEEPSEEK_API_KEY') ?: '';

if ($openAiApiKey === '' || $yandexApiKey === '' || $yandexFolderId === '' || $deepSeekApiKey === '') {
    throw new RuntimeException('Set OPENAI_API_KEY, YANDEX_API_KEY, YANDEX_FOLDER_ID, DEEPSEEK_API_KEY environment variables.');
}

$ai = Ai::make()
    ->withYandex($yandexApiKey, $yandexFolderId)
    ->withOpenAi($openAiApiKey)
    ->withDeepSeek($deepSeekApiKey)
    ->router(
        Router::fallback([
            'yandex:yandexgpt-lite',
            'openai:gpt-4.1-mini',
            'deepseek:deepseek-chat',
        ])
    );

$response = $ai->chat(
    ChatRequest::make()
        ->system('You are a concise assistant.')
        ->user('Summarize this incident report in 3 bullet points.')
);

echo $response->text();
```

See `examples/` for more scenarios.
