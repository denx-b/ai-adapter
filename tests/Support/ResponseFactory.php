<?php

declare(strict_types=1);

namespace AiAdapter\Tests\Support;

use AiAdapter\DTO\ChatResponse;
use AiAdapter\DTO\ResponseMeta;
use AiAdapter\DTO\Usage;

final class ResponseFactory
{
    /**
     * @param array<string, mixed> $raw
     */
    public static function text(string $text, string $provider, string $model, array $raw = []): ChatResponse
    {
        return new ChatResponse(
            $text,
            Usage::empty(),
            new ResponseMeta($provider, $model),
            $raw,
        );
    }
}
