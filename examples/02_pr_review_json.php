<?php

declare(strict_types=1);

use AiAdapter\Ai;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\DTO\FilePart;
use AiAdapter\DTO\OutputFormat;

require __DIR__ . '/../vendor/autoload.php';

$ai = Ai::make()
    ->withDeepSeek($_ENV['DEEPSEEK_API_KEY']);

$request = ChatRequest::make()
    ->provider('deepseek')
    ->model('deepseek-chat')
    ->system('Ты строгий ревьюер PHP 8.3. Ищи баги и регрессии.')
    ->user('Проанализируй изменения и верни JSON с проблемами.')
    ->files([
        FilePart::fromPath(__DIR__ . '/fixtures/pr.diff'),
        FilePart::fromPath(__DIR__ . '/fixtures/phpstan-report.txt'),
    ])
    ->output(OutputFormat::jsonSchema([
        'type' => 'object',
        'properties' => [
            'findings' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'severity' => ['type' => 'string'],
                        'file' => ['type' => 'string'],
                        'line' => ['type' => 'integer'],
                        'issue' => ['type' => 'string'],
                        'fix' => ['type' => 'string'],
                    ],
                    'required' => ['severity', 'file', 'issue'],
                ],
            ],
        ],
        'required' => ['findings'],
    ]));

$response = $ai->chat($request);

print_r($response->json());
