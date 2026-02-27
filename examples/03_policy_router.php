<?php

declare(strict_types=1);

use AiAdapter\Ai;
use AiAdapter\Core\Router;
use AiAdapter\DTO\ChatRequest;

require __DIR__ . '/../vendor/autoload.php';

$ai = Ai::make()
    ->withOpenAi($_ENV['OPENAI_API_KEY'])
    ->withYandex($_ENV['YANDEX_API_KEY'], $_ENV['YANDEX_FOLDER_ID'])
    ->withDeepSeek($_ENV['DEEPSEEK_API_KEY'])
    ->router(
        Router::policy(static function (ChatRequest $request): string {
            $context = $request->contextData();

            if (($context['region'] ?? null) === 'ru') {
                return 'yandex:yandexgpt-lite';
            }

            if (($context['task'] ?? null) === 'code_review') {
                return 'deepseek:deepseek-chat';
            }

            return 'openai:gpt-4.1-mini';
        })
    );

$response = $ai->chat(
    ChatRequest::make()
        ->user('Суммаризируй переписку и сформируй next actions.')
        ->context([
            'region' => 'ru',
            'task' => 'summary',
        ])
);

echo $response->text() . PHP_EOL;
echo 'provider=' . ($response->meta()->provider() ?? 'unknown') . PHP_EOL;
