<?php

declare(strict_types=1);

namespace AiAdapter\Providers\DeepSeek;

use AiAdapter\Providers\Support\OpenAiCompatibleProvider;

final class DeepSeekProvider extends OpenAiCompatibleProvider
{
    public function __construct(
        string $apiKey,
        string $defaultModel = 'deepseek-chat',
        string $baseUri = 'https://api.deepseek.com/v1',
    ) {
        parent::__construct($apiKey, $baseUri, $defaultModel);
    }

    public function name(): string
    {
        return 'deepseek';
    }
}
