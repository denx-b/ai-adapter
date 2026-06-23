# ai-adapter

Provider-agnostic PHP library for chat-style LLM requests.

## MVP scope

- PHP `^8.2`
- PSR transport under the hood (`Guzzle` + `Nyholm PSR-7`)
- Chat API only
- Providers: OpenAI, YandexGPT, DeepSeek
- Router fallback policy (auth/timeout/429/5xx)
- Optional structured output with JSON schema

## Install

```bash
composer require denx-b/ai-adapter
```

## Quick start

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use AiAdapter\Ai;
use AiAdapter\Core\Router;
use AiAdapter\DTO\ChatRequest;
use Dotenv\Dotenv;

Dotenv::createUnsafeImmutable(__DIR__)->safeLoad();

$openAiApiKey = getenv('OPENAI_API_KEY') ?: '';
$yandexApiKey = getenv('YANDEX_API_KEY') ?: '';
$yandexFolderId = getenv('YANDEX_FOLDER_ID') ?: '';
$deepSeekApiKey = getenv('DEEPSEEK_API_KEY') ?: '';

$ai = Ai::make()
    ->withYandex($yandexApiKey, $yandexFolderId)
    ->withOpenAi($openAiApiKey)
    ->withDeepSeek($deepSeekApiKey)
    ->router(
        Router::fallback([
            'yandex',
            'openai',
            'deepseek',
        ])
    );

$response = $ai->chat(
    ChatRequest::make()
        ->system('You are a concise assistant.')
        ->user('Summarize this incident report in 3 bullet points.')
);

echo $response->text();
```

`Router::fallback(['yandex', 'openai', 'deepseek'])` использует `defaultModel` каждого провайдера.
Если нужна конкретная модель, можно указать `provider:model`, например `openai:gpt-5.4-mini`.

## Актуальные модели для chat/text (23 июня 2026)

Библиотека не ограничивает список моделей жестко: в `model()` и `provider:model` можно передать любой ID, который поддерживает выбранный провайдер и ваш аккаунт. Ниже практичный shortlist для текущей chat/text реализации.

| Провайдер | Model ID | Когда выбирать |
| --- | --- | --- |
| OpenAI | `gpt-5.5` | Флагман для сложного reasoning, кода и профессиональных задач. |
| OpenAI | `gpt-5.4` | Более доступная сильная модель для кода и рабочих задач. |
| OpenAI | `gpt-5.4-mini` | Дефолт библиотеки: хороший баланс качества, цены и задержки. |
| OpenAI | `gpt-5.4-nano` | Минимальная цена и задержка для простых массовых задач. |
| Yandex | `aliceai-llm` | Дефолт библиотеки: флагман Yandex для диалоговых ассистентов и сложных задач на русском. |
| Yandex | `yandexgpt-5.1` | Актуальная версия YandexGPT Pro для RAG, анализа документов и извлечения данных. |
| Yandex | `yandexgpt-5-pro` | Предыдущая версия YandexGPT Pro 5, если она уже проверена в продукте. |
| Yandex | `yandexgpt-5-lite` | Быстрый и дешевый вариант для простых текстовых сценариев. |
| DeepSeek | `deepseek-v4-flash` | Дефолт библиотеки: актуальный быстрый и дешевый режим DeepSeek. |
| DeepSeek | `deepseek-v4-pro` | Более сильный режим DeepSeek для задач, где качество важнее цены и задержки. |

Пример выбора "лучшего баланса" через дефолты:

```php
$ai = Ai::make()
    ->withYandex($yandexApiKey, $yandexFolderId, defaultModel: 'aliceai-llm')
    ->withOpenAi($openAiApiKey, defaultModel: 'gpt-5.4-mini')
    ->withDeepSeek($deepSeekApiKey, defaultModel: 'deepseek-v4-flash')
    ->router(Router::fallback(['yandex', 'openai', 'deepseek']));
```

Примечания:
- Для Yandex можно передать короткий ID (`aliceai-llm`) или полный URI (`gpt://<folder_ID>/aliceai-llm`).
- Если у провайдера нет доступа к выбранной модели, запрос завершится ошибкой и роутер перейдет к следующему провайдеру (по fallback-политике).

See `examples/` for more scenarios.
