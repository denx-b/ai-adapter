<?php

declare(strict_types=1);

namespace AiAdapter\Core;

use AiAdapter\Contracts\ProviderInterface;
use AiAdapter\Exception\ModelNotFoundException;

final class ProviderRegistry
{
    /**
     * @var array<string, ProviderInterface>
     */
    private array $providers = [];

    public function register(ProviderInterface $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    public function get(string $name): ProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new ModelNotFoundException('Provider is not registered: ' . $name);
        }

        return $this->providers[$name];
    }

    public function count(): int
    {
        return count($this->providers);
    }

    /**
     * @return array<string, ProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
