# AI-адаптер

PHP-библиотека для запросов к разным AI-провайдерам через единый интерфейс.

## Возможности

- единый ООП-интерфейс
- поддержка: OpenAI, YandexGPT, DeepSeek, Claude
- fallback-роутинг при ошибках авторизации, таймаутах, rate limit и 5xx
- возможность структурировать ответ через JSON Schema
- PSR-транспорт под капотом (`Guzzle` + `Nyholm PSR-7`)

## Установка

```bash
composer require denx-b/ai-adapter
```

## Запрос к ChatGPT с моделью по-умолчанию
```php
<?php

require __DIR__ . '/vendor/autoload.php';

\Dotenv\Dotenv::createUnsafeImmutable(__DIR__)->safeLoad();

$response = \AiAdapter\Ai::make()
    ->withOpenAi((string) getenv('OPENAI_API_KEY'))
    ->chat(
        \AiAdapter\DTO\ChatRequest::make()
            ->user('Объясни простыми словами, зачем нужен fallback между несколькими AI-провайдерами.')
    );

echo $response->text();
```

## Запрос к ChatGPT с указанием модели gpt-5.5
```php
$response = \AiAdapter\Ai::make()
    ->withOpenAi((string) getenv('OPENAI_API_KEY'))
    ->chat(
        \AiAdapter\DTO\ChatRequest::make()
            ->model('gpt-5.5')
            ->user('Объясни простыми словами, зачем нужен fallback между несколькими AI-провайдерами.')
    );

echo $response->text();
```
## Пример роутинга
Если у провайдера нет доступа к выбранной модели, запрос завершится ошибкой и роутер перейдет к следующему провайдеру (по fallback-политике).
```php
<?php

require __DIR__ . '/vendor/autoload.php';

\Dotenv\Dotenv::createUnsafeImmutable(__DIR__)->safeLoad();

$openAiApiKey = getenv('OPENAI_API_KEY') ?: '';
$yandexApiKey = getenv('YANDEX_API_KEY') ?: '';
$yandexFolderId = getenv('YANDEX_FOLDER_ID') ?: '';
$deepSeekApiKey = getenv('DEEPSEEK_API_KEY') ?: '';
$claudeApiKey = getenv('CLAUDE_API_KEY') ?: '';

$ai = \AiAdapter\Ai::make()
    ->withYandex($yandexApiKey, $yandexFolderId)
    ->withOpenAi($openAiApiKey)
    ->withDeepSeek($deepSeekApiKey)
    ->withClaude($claudeApiKey)
    ->router(
        \AiAdapter\Core\Router::fallback([
            'yandex',
            'openai',
            'deepseek',
            'claude',
        ])
    );

$response = $ai->chat(
    \AiAdapter\DTO\ChatRequest::make()
        ->system('Ты краткий и полезный ассистент.')
        ->user('Объясни простыми словами, зачем нужен fallback между несколькими AI-провайдерами.')
);

echo $response->text();
```

## Актуальные модели для chat/text (23 июня 2026)

Библиотека не ограничивает список моделей жестко: в `model()` и `provider:model` можно передать любой ID, который поддерживает выбранный провайдер и ваш аккаунт. Ниже практичный shortlist для текущей chat/text реализации.

| Провайдер | Model ID | Когда выбирать |
| --- | --- | --- |
| OpenAI | `gpt-5.5` | Флагман для сложного reasoning, кода и профессиональных задач. |
| OpenAI | `gpt-5.4` | Более доступная сильная модель для кода и рабочих задач. |
| OpenAI | `gpt-5.4-mini` | Дефолт библиотеки: хороший баланс качества, цены и задержки. |
| OpenAI | `gpt-5.4-nano` | Минимальная цена и задержка для простых массовых задач. |
| Claude | `claude-fable-5` | Самая сильная широко доступная модель Anthropic для сложного reasoning и long-horizon задач. |
| Claude | `claude-opus-4-8` | Максимальное качество Opus-класса для сложного кода и агентских сценариев. |
| Claude | `claude-sonnet-4-6` | Дефолт библиотеки: лучший баланс скорости и интеллекта в линейке Claude. |
| Claude | `claude-haiku-4-5` | Самый быстрый вариант Claude для массовых и latency-sensitive задач. |
| Yandex | `aliceai-llm` | Дефолт библиотеки: флагман Yandex для диалоговых ассистентов и сложных задач на русском. |
| Yandex | `yandexgpt-5.1` | Актуальная версия YandexGPT Pro для RAG, анализа документов и извлечения данных. |
| Yandex | `yandexgpt-5-pro` | Предыдущая версия YandexGPT Pro 5, если она уже проверена в продукте. |
| Yandex | `yandexgpt-5-lite` | Быстрый и дешевый вариант для простых текстовых сценариев. |
| DeepSeek | `deepseek-v4-flash` | Дефолт библиотеки: актуальный быстрый и дешевый режим DeepSeek. |
| DeepSeek | `deepseek-v4-pro` | Более сильный режим DeepSeek для задач, где качество важнее цены и задержки. |

Пример выбора "лучшего баланса" через дефолты:

```php
$ai = \AiAdapter\Ai::make()
    ->withYandex($yandexApiKey, $yandexFolderId, defaultModel: 'aliceai-llm')
    ->withOpenAi($openAiApiKey, defaultModel: 'gpt-5.4-mini')
    ->withDeepSeek($deepSeekApiKey, defaultModel: 'deepseek-v4-flash')
    ->withClaude($claudeApiKey, defaultModel: 'claude-sonnet-4-6')
    ->router(\AiAdapter\Core\Router::fallback(['yandex', 'openai', 'deepseek', 'claude']));
```

Примечания:
- Для Yandex можно передать короткий ID (`aliceai-llm`) или полный URI (`gpt://<folder_ID>/aliceai-llm`).

