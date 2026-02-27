<?php

declare(strict_types=1);

namespace AiAdapter\Providers\OpenAI;

use AiAdapter\Providers\Support\OpenAiCompatibleProvider;

final class OpenAiProvider extends OpenAiCompatibleProvider
{
    public function __construct(
        string $apiKey,
        string $defaultModel = 'gpt-4.1-mini',
        string $baseUri = 'https://api.openai.com/v1',
    ) {
        parent::__construct($apiKey, $baseUri, $defaultModel);
    }

    public function name(): string
    {
        return 'openai';
    }
}
