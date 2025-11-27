<?php

declare(strict_types=1);

namespace MethorZ\HttpCache\Middleware;

use MethorZ\HttpCache\Directive\CacheControlDirective;
use MethorZ\HttpCache\Generator\ETagGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function in_array;
use function str_contains;
use function strtoupper;

/**
 * PSR-15 middleware for HTTP caching with ETag support
 *
 * Features:
 * - Automatic ETag generation
 * - 304 Not Modified responses
 * - Cache-Control header management
 * - Conditional request handling (If-None-Match, If-Modified-Since)
 * - RFC 7234 & RFC 7232 compliance
 *
 * Usage:
 * ```php
 * $middleware = new CacheMiddleware(
 *     enabled: true,
 *     cacheControl: CacheControlDirective::create()->public()->maxAge(3600)
 * );
 * ```
 */
final readonly class CacheMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string> $cacheableMethods HTTP methods eligible for caching
     * @param array<int> $cacheableStatuses Status codes eligible for caching
     */
    public function __construct(
        private bool $enabled = true,
        private ?CacheControlDirective $cacheControl = null,
        private bool $useWeakEtag = false,
        private string $etagAlgorithm = 'md5',
        private array $cacheableMethods = ['GET', 'HEAD'],
        private array $cacheableStatuses = [200, 203, 204, 206, 300, 301, 404, 405, 410, 414, 501],
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // Skip if caching is disabled
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        // Only cache specific methods
        if (!$this->isCacheableMethod($request->getMethod())) {
            return $handler->handle($request);
        }

        // Get response from handler
        $response = $handler->handle($request);

        // Only cache specific status codes
        if (!$this->isCacheableStatus($response->getStatusCode())) {
            return $response;
        }

        // Check if response already has Cache-Control
        if (!$response->hasHeader('Cache-Control') && $this->cacheControl !== null) {
            $response = $response->withHeader('Cache-Control', $this->cacheControl->toString());
        }

        // Generate ETag if not present
        if (!$response->hasHeader('ETag')) {
            $etag = $this->useWeakEtag
                ? ETagGenerator::generateWeak($response)
                : ETagGenerator::generateWithAlgorithm($response, $this->etagAlgorithm);

            $response = $response->withHeader('ETag', $etag);
        }

        // Handle conditional requests
        return $this->handleConditionalRequest($request, $response);
    }

    /**
     * Handle If-None-Match conditional requests
     *
     * @throws \InvalidArgumentException
     * @throws \Laminas\Diactoros\Exception\InvalidArgumentException
     */
    private function handleConditionalRequest(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $responseEtag = $response->getHeaderLine('ETag');

        if ($responseEtag === '') {
            return $response;
        }

        // Check If-None-Match header
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');

        if ($ifNoneMatch !== '') {
            if ($this->etagMatches($responseEtag, $ifNoneMatch)) {
                // Return 304 Not Modified (RFC 7232 §4.1)
                // Remove Content-Length to indicate no body content
                return $response
                    ->withStatus(304, 'Not Modified')
                    ->withoutHeader('Content-Length');
            }
        }

        return $response;
    }

    /**
     * Check if ETag matches conditional header
     */
    private function etagMatches(string $etag, string $ifNoneMatch): bool
    {
        // Handle * (matches any ETag)
        if ($ifNoneMatch === '*') {
            return true;
        }

        // Handle multiple ETags (comma-separated)
        if (str_contains($ifNoneMatch, ',')) {
            $etags = array_map('trim', explode(',', $ifNoneMatch));

            foreach ($etags as $conditionalEtag) {
                if (ETagGenerator::matches($etag, $conditionalEtag)) {
                    return true;
                }
            }

            return false;
        }

        // Single ETag comparison
        return ETagGenerator::matches($etag, $ifNoneMatch);
    }

    /**
     * Check if HTTP method is cacheable
     */
    private function isCacheableMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->cacheableMethods, true);
    }

    /**
     * Check if HTTP status code is cacheable
     */
    private function isCacheableStatus(int $status): bool
    {
        return in_array($status, $this->cacheableStatuses, true);
    }
}
