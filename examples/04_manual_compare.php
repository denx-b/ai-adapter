<?php

declare(strict_types=1);

use AiAdapter\Ai;
use AiAdapter\DTO\ChatRequest;
use AiAdapter\Exception\ProviderChainException;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

Dotenv::createUnsafeImmutable(dirname(__DIR__))->safeLoad();

$system = 'You are a concise assistant. Reply in Russian.';
$prompt = $argv[1] ?? 'Объясни, что такое fallback роутинг в 3 коротких пунктах.';
$outputPath = $argv[2] ?? (__DIR__ . '/output/manual_compare_' . date('Ymd_His') . '.json');

$request = ChatRequest::make()
    ->system($system)
    ->user($prompt)
    ->temperature(0.2);

$providers = [
    'openai' => [
        'enabled' => (string) getenv('OPENAI_API_KEY') !== '',
        'run' => static fn () => Ai::make()
            ->withOpenAi((string) getenv('OPENAI_API_KEY'))
            ->chat($request->target('openai', 'gpt-4.1-mini')),
    ],
    'yandex' => [
        'enabled' => (string) getenv('YANDEX_API_KEY') !== '' && (string) getenv('YANDEX_FOLDER_ID') !== '',
        'run' => static fn () => Ai::make()
            ->withYandex((string) getenv('YANDEX_API_KEY'), (string) getenv('YANDEX_FOLDER_ID'))
            ->chat($request->target('yandex', 'yandexgpt-lite')),
    ],
    'deepseek' => [
        'enabled' => (string) getenv('DEEPSEEK_API_KEY') !== '',
        'run' => static fn () => Ai::make()
            ->withDeepSeek((string) getenv('DEEPSEEK_API_KEY'))
            ->chat($request->target('deepseek', 'deepseek-chat')),
    ],
];

$report = [
    'prompt' => $prompt,
    'system' => $system,
    'started_at' => date(DATE_ATOM),
    'results' => [],
];

foreach ($providers as $name => $config) {
    echo str_repeat('=', 80) . PHP_EOL;
    echo strtoupper($name) . PHP_EOL;
    echo str_repeat('-', 80) . PHP_EOL;

    if ($config['enabled'] !== true) {
        echo "Skipped: missing API credentials in environment." . PHP_EOL;
        $report['results'][] = [
            'provider' => $name,
            'status' => 'skipped',
            'reason' => 'missing credentials',
        ];
        continue;
    }

    try {
        $startedAt = microtime(true);
        $response = $config['run']();
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
        $text = trim($response->text());

        echo "provider: " . ($response->meta()->provider() ?? 'unknown') . PHP_EOL;
        echo "model: " . ($response->meta()->model() ?? 'unknown') . PHP_EOL;
        echo "latency_ms: " . $latencyMs . PHP_EOL;
        echo "total_tokens: " . ($response->usage()->totalTokens() ?? 'n/a') . PHP_EOL;
        echo PHP_EOL;
        echo $text . PHP_EOL;

        $report['results'][] = [
            'provider' => $name,
            'status' => 'ok',
            'model' => $response->meta()->model(),
            'latency_ms' => $latencyMs,
            'total_tokens' => $response->usage()->totalTokens(),
            'text' => $text,
        ];
    } catch (Throwable $exception) {
        echo "Failed: " . $exception::class . PHP_EOL;
        echo "Message: " . $exception->getMessage() . PHP_EOL;

        $result = [
            'provider' => $name,
            'status' => 'failed',
            'error_class' => $exception::class,
            'error_message' => $exception->getMessage(),
        ];

        if ($exception instanceof ProviderChainException) {
            $result['attempts'] = $exception->attempts();
            foreach ($exception->attempts() as $attempt) {
                $attemptProvider = (string) ($attempt['provider'] ?? 'unknown');
                $attemptModel = (string) ($attempt['model'] ?? 'unknown');
                $attemptError = (string) ($attempt['error'] ?? 'unknown');
                $attemptMessage = (string) ($attempt['message'] ?? '');
                echo "attempt provider={$attemptProvider} model={$attemptModel}" . PHP_EOL;
                echo "attempt error={$attemptError}" . PHP_EOL;
                if ($attemptMessage !== '') {
                    echo "attempt message={$attemptMessage}" . PHP_EOL;
                }
            }
        }

        $previous = $exception->getPrevious();
        if ($previous !== null) {
            $result['previous_error_class'] = $previous::class;
            $result['previous_error_message'] = $previous->getMessage();
            echo "previous: " . $previous::class . PHP_EOL;
            echo "previous message: " . $previous->getMessage() . PHP_EOL;
        }

        $report['results'][] = $result;
    }
}

$report['finished_at'] = date(DATE_ATOM);
$outputDir = dirname($outputPath);
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0775, true);
}

file_put_contents(
    $outputPath,
    json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
);

echo str_repeat('=', 80) . PHP_EOL;
echo "Saved report: {$outputPath}" . PHP_EOL;
