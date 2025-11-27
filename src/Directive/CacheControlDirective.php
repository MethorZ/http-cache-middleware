<?php

declare(strict_types=1);

namespace MethorZ\HttpCache\Directive;

use function implode;

/**
 * Builds Cache-Control header directives
 *
 * Provides a fluent interface for constructing RFC 7234 compliant
 * Cache-Control headers.
 *
 * Usage:
 * ```php
 * $directive = CacheControlDirective::create()
 *     ->public()
 *     ->maxAge(3600)
 *     ->mustRevalidate();
 *
 * $header = $directive->toString(); // "public, max-age=3600, must-revalidate"
 * ```
 */
final class CacheControlDirective
{
    /**
     * @var array<string, int|string|bool> Directive values
     */
    private array $directives = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * Response may be cached by any cache (opposite of private)
     */
    public function public(): self
    {
        $this->directives['public'] = true;
        unset($this->directives['private']);

        return $this;
    }

    /**
     * Response intended for single user, not shared caches
     */
    public function private(): self
    {
        $this->directives['private'] = true;
        unset($this->directives['public']);

        return $this;
    }

    /**
     * Response must not be cached
     */
    public function noCache(): self
    {
        $this->directives['no-cache'] = true;

        return $this;
    }

    /**
     * Response must not be stored anywhere
     */
    public function noStore(): self
    {
        $this->directives['no-store'] = true;

        return $this;
    }

    /**
     * Maximum age in seconds the response is considered fresh
     */
    public function maxAge(int $seconds): self
    {
        $this->directives['max-age'] = $seconds;

        return $this;
    }

    /**
     * Shared cache maximum age (overrides max-age for shared caches)
     */
    public function sMaxAge(int $seconds): self
    {
        $this->directives['s-maxage'] = $seconds;

        return $this;
    }

    /**
     * Cache must revalidate when stale
     */
    public function mustRevalidate(): self
    {
        $this->directives['must-revalidate'] = true;

        return $this;
    }

    /**
     * Shared cache must revalidate when stale
     */
    public function proxyRevalidate(): self
    {
        $this->directives['proxy-revalidate'] = true;

        return $this;
    }

    /**
     * Cache must not transform the response
     */
    public function noTransform(): self
    {
        $this->directives['no-transform'] = true;

        return $this;
    }

    /**
     * Cache can serve stale response while revalidating
     */
    public function staleWhileRevalidate(int $seconds): self
    {
        $this->directives['stale-while-revalidate'] = $seconds;

        return $this;
    }

    /**
     * Cache can serve stale response if origin is unreachable
     */
    public function staleIfError(int $seconds): self
    {
        $this->directives['stale-if-error'] = $seconds;

        return $this;
    }

    /**
     * Response is immutable (will never change)
     */
    public function immutable(): self
    {
        $this->directives['immutable'] = true;

        return $this;
    }

    /**
     * Convert directives to Cache-Control header string
     */
    public function toString(): string
    {
        $parts = [];

        foreach ($this->directives as $name => $value) {
            if ($value === true) {
                $parts[] = $name;
            } else {
                $parts[] = $name . '=' . $value;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Get directives as array
     *
     * @return array<string, int|string|bool>
     */
    public function toArray(): array
    {
        return $this->directives;
    }
}
