<?php

declare(strict_types=1);

namespace AiAdapter\Core\Transport;

use GuzzleHttp\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class DefaultTransport
{
    public static function client(): ClientInterface
    {
        return new Client();
    }

    public static function requestFactory(): RequestFactoryInterface
    {
        return new Psr17Factory();
    }

    public static function streamFactory(): StreamFactoryInterface
    {
        return new Psr17Factory();
    }
}
