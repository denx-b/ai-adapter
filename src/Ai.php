<?php

declare(strict_types=1);

namespace AiAdapter;

use AiAdapter\Core\AiClient;

final class Ai
{
    public static function make(): AiClient
    {
        return new AiClient();
    }
}
