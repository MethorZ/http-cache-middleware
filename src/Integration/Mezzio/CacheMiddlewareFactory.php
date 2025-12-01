<?php

declare(strict_types=1);

namespace MethorZ\HttpCache\Integration\Mezzio;

use MethorZ\HttpCache\Directive\CacheControlDirective;
use MethorZ\HttpCache\Middleware\CacheMiddleware;
use Psr\Container\ContainerInterface;

/**
 * Mezzio/Laminas ServiceManager factory for CacheMiddleware
 *
 * Reads configuration from 'http_cache' key in container config.
 * Provides sensible defaults for zero-config usage.
 *
 * NOTE: This is a Mezzio-specific integration. For other frameworks,
 * you can manually instantiate CacheMiddleware with your desired configuration.
 *
 * Configuration example (config/autoload/http-cache.global.php):
 * ```php
 * return [
 *     'http_cache' => [
 *         'enabled' => true,
 *         'max_age' => 3600,
 *         'use_weak_etag' => false,
 *         'etag_algorithm' => 'md5',
 *     ],
 * ];
 * ```
 */
final class CacheMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): CacheMiddleware
    {
        // Read configuration from container (if available)
        $config = $container->has('config') ? $container->get('config') : [];

        /** @var array{enabled?: bool, max_age?: int, use_weak_etag?: bool, etag_algorithm?: string} $cacheConfig */
        $cacheConfig = $config['http_cache'] ?? [];

        // Extract configuration with sensible defaults
        $enabled = $cacheConfig['enabled'] ?? true;
        $maxAge = $cacheConfig['max_age'] ?? 300; // 5 minutes default
        $useWeakEtag = $cacheConfig['use_weak_etag'] ?? false;
        $etagAlgorithm = $cacheConfig['etag_algorithm'] ?? 'md5';

        // Build Cache-Control directive
        $cacheControl = CacheControlDirective::create()
            ->public()
            ->maxAge($maxAge);

        return new CacheMiddleware(
            enabled: $enabled,
            cacheControl: $cacheControl,
            useWeakEtag: $useWeakEtag,
            etagAlgorithm: $etagAlgorithm,
        );
    }
}
