<?php

declare(strict_types=1);

namespace MethorZ\HttpCache\Integration\Mezzio;

use MethorZ\HttpCache\Middleware\CacheMiddleware;

/**
 * Mezzio configuration provider for the http-cache-middleware package
 *
 * Registers the CacheMiddleware with automatic configuration.
 *
 * NOTE: This is a Mezzio-specific integration. For other frameworks,
 * you can manually instantiate CacheMiddleware with your desired configuration.
 *
 * Usage in config/config.php:
 * ```php
 * $aggregator = new ConfigAggregator([
 *     MethorZ\HttpCache\Integration\Mezzio\ConfigProvider::class,
 *     // ... other providers
 * ]);
 * ```
 */
final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                CacheMiddleware::class => CacheMiddlewareFactory::class,
            ],
        ];
    }
}
