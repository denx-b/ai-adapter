<?php

declare(strict_types=1);

use AiAdapter\Ai;
use AiAdapter\Core\Router;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\DTO\FilePart;

require __DIR__ . '/../vendor/autoload.php';

$ai = Ai::make()
    ->withYandex($_ENV['YANDEX_API_KEY'], $_ENV['YANDEX_FOLDER_ID'])
    ->withOpenAi($_ENV['OPENAI_API_KEY'])
    ->router(
        Router::fallback([
            'yandex:yandexgpt-lite',
            'openai:gpt-4.1-mini',
        ])
    );

$request = ChatRequest::make()
    ->system('Ты сотрудник поддержки. Отвечай по-русски, кратко, без жаргона.')
    ->user('Клиент просит возврат за подписку спустя 45 дней.')
    ->context([
        'plan' => 'pro',
        'sla_hours' => 24,
        'customer_language' => 'ru',
    ])
    ->files([
        FilePart::fromPath(__DIR__ . '/fixtures/faq.md'),
        FilePart::fromPath(__DIR__ . '/fixtures/refund_policy.md'),
    ])
    ->temperature(0.2);

$response = $ai->chat($request);

echo $response->text() . PHP_EOL;
echo 'provider=' . ($response->meta()->provider() ?? 'unknown') . PHP_EOL;
echo 'model=' . ($response->meta()->model() ?? 'unknown') . PHP_EOL;
