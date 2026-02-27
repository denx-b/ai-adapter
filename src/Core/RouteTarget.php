<?php

declare(strict_types=1);

namespace AiAdapter\Core;

use AiAdapter\Exception\ValidationException;

final class RouteTarget
{
    public function __construct(
        private readonly string $provider,
        private readonly string $model,
    ) {
        if ($provider === '' || $model === '') {
            throw new ValidationException('Route target requires non-empty provider and model.');
        }
    }

    public static function fromString(string $providerModel): self
    {
        [$provider, $model] = array_pad(explode(':', $providerModel, 2), 2, null);
        if ($provider === null || $model === null || $provider === '' || $model === '') {
            throw new ValidationException('Route target must match provider:model format.');
        }

        return new self($provider, $model);
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function model(): string
    {
        return $this->model;
    }
}
