<?php

declare(strict_types=1);

namespace MethorZ\HttpCache\Tests\Unit\Directive;

use MethorZ\HttpCache\Directive\CacheControlDirective;
use PHPUnit\Framework\TestCase;

final class CacheControlDirectiveTest extends TestCase
{
    public function testCreatesDirective(): void
    {
        $directive = CacheControlDirective::create();
        $this->assertInstanceOf(CacheControlDirective::class, $directive);
    }

    public function testPublicDirective(): void
    {
        $directive = CacheControlDirective::create()->public();
        $this->assertStringContainsString('public', $directive->toString());
    }

    public function testPrivateDirective(): void
    {
        $directive = CacheControlDirective::create()->private();
        $this->assertStringContainsString('private', $directive->toString());
    }

    public function testPublicOverridesPrivate(): void
    {
        $directive = CacheControlDirective::create()->private()->public();
        $result = $directive->toString();

        $this->assertStringContainsString('public', $result);
        $this->assertStringNotContainsString('private', $result);
    }

    public function testNoCacheDirective(): void
    {
        $directive = CacheControlDirective::create()->noCache();
        $this->assertStringContainsString('no-cache', $directive->toString());
    }

    public function testNoStoreDirective(): void
    {
        $directive = CacheControlDirective::create()->noStore();
        $this->assertStringContainsString('no-store', $directive->toString());
    }

    public function testMaxAgeDirective(): void
    {
        $directive = CacheControlDirective::create()->maxAge(3600);
        $this->assertStringContainsString('max-age=3600', $directive->toString());
    }

    public function testSMaxAgeDirective(): void
    {
        $directive = CacheControlDirective::create()->sMaxAge(7200);
        $this->assertStringContainsString('s-maxage=7200', $directive->toString());
    }

    public function testMustRevalidateDirective(): void
    {
        $directive = CacheControlDirective::create()->mustRevalidate();
        $this->assertStringContainsString('must-revalidate', $directive->toString());
    }

    public function testProxyRevalidateDirective(): void
    {
        $directive = CacheControlDirective::create()->proxyRevalidate();
        $this->assertStringContainsString('proxy-revalidate', $directive->toString());
    }

    public function testNoTransformDirective(): void
    {
        $directive = CacheControlDirective::create()->noTransform();
        $this->assertStringContainsString('no-transform', $directive->toString());
    }

    public function testStaleWhileRevalidateDirective(): void
    {
        $directive = CacheControlDirective::create()->staleWhileRevalidate(60);
        $this->assertStringContainsString('stale-while-revalidate=60', $directive->toString());
    }

    public function testStaleIfErrorDirective(): void
    {
        $directive = CacheControlDirective::create()->staleIfError(120);
        $this->assertStringContainsString('stale-if-error=120', $directive->toString());
    }

    public function testImmutableDirective(): void
    {
        $directive = CacheControlDirective::create()->immutable();
        $this->assertStringContainsString('immutable', $directive->toString());
    }

    public function testFluentInterface(): void
    {
        $directive = CacheControlDirective::create()
            ->public()
            ->maxAge(3600)
            ->mustRevalidate();

        $result = $directive->toString();

        $this->assertStringContainsString('public', $result);
        $this->assertStringContainsString('max-age=3600', $result);
        $this->assertStringContainsString('must-revalidate', $result);
    }

    public function testToArrayReturnsDirectives(): void
    {
        $directive = CacheControlDirective::create()
            ->public()
            ->maxAge(3600);

        $array = $directive->toArray();

        $this->assertArrayHasKey('public', $array);
        $this->assertArrayHasKey('max-age', $array);
        $this->assertTrue($array['public']);
        $this->assertSame(3600, $array['max-age']);
    }

    public function testComplexDirectiveCombination(): void
    {
        $directive = CacheControlDirective::create()
            ->public()
            ->maxAge(31536000)
            ->immutable();

        $this->assertSame('public, max-age=31536000, immutable', $directive->toString());
    }
}
