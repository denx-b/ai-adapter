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
Если нужна конкретная модель, можно указать `provider:model`, например `openai:gpt-4.1-mini`.

## Рекомендованные модели (актуально на 27 февраля 2026)

Ниже практичный shortlist для chat/text-generation сценариев в этой библиотеке.

| Провайдер | Model ID | Когда выбирать |
| --- | --- | --- |
| OpenAI | `gpt-5.2` | Лучшее общее качество для сложных задач, кода и agentic-сценариев. |
| OpenAI | `gpt-5.2-pro` | Максимальное качество для сложных рассуждений, если допустима более высокая цена/латентность. |
| OpenAI | `gpt-5-mini` | Оптимальный баланс цена/скорость/качество для продакшн-потоков. |
| OpenAI | `gpt-5-nano` | Самый дешевый и быстрый вариант для простых задач: классификация, короткие суммаризации, high-throughput. |
| OpenAI | `gpt-4.1` | Сильная non-reasoning модель, когда нужен стабильный детерминированный текст/tool-calling профиль без тяжелого reasoning. |
| OpenAI | `gpt-4.1-mini` | Быстрый и недорогой рабочий вариант для типовых бизнес-задач. |
| Yandex | `yandexgpt` | Лучшее качество среди базовых YandexGPT моделей в common instance. |
| Yandex | `yandexgpt-lite` | Быстрее и дешевле, подходит для массовых простых запросов. |
| DeepSeek | `deepseek-chat` | Универсальный режим для большинства задач (non-thinking). |
| DeepSeek | `deepseek-reasoner` | Для сложных reasoning-задач, где важнее точность рассуждений, чем скорость. |

Пример выбора "лучшего баланса" через дефолты:

```php
$ai = Ai::make()
    ->withYandex($yandexApiKey, $yandexFolderId, defaultModel: 'yandexgpt')
    ->withOpenAi($openAiApiKey, defaultModel: 'gpt-5-mini')
    ->withDeepSeek($deepSeekApiKey, defaultModel: 'deepseek-chat')
    ->router(Router::fallback(['yandex', 'openai', 'deepseek']));
```

Примечания:
- Для Yandex-адаптера текущая реализация добавляет ветку `/latest` автоматически.
- Если у провайдера нет доступа к выбранной модели, запрос завершится ошибкой и роутер перейдет к следующему провайдеру (по fallback-политике).

See `examples/` for more scenarios.
