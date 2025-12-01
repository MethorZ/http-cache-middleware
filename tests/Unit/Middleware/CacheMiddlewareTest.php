<?php

declare(strict_types=1);

namespace MethorZ\HttpCache\Tests\Unit\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use MethorZ\HttpCache\Directive\CacheControlDirective;
use MethorZ\HttpCache\Middleware\CacheMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CacheMiddlewareTest extends TestCase
{
    public function testAddsEtagToResponse(): void
    {
        $middleware = new CacheMiddleware();
        $request = new ServerRequest('GET', '/test');
        $response = $this->createResponse('test content');
        $handler = $this->createHandler($response);

        $result = $middleware->process($request, $handler);

        $this->assertTrue($result->hasHeader('ETag'));
    }

    public function testAddsCacheControlHeader(): void
    {
        $cacheControl = CacheControlDirective::create()->public()->maxAge(3600);
        $middleware = new CacheMiddleware(cacheControl: $cacheControl);

        $request = new ServerRequest('GET', '/test');
        $response = $this->createResponse('test');
        $handler = $this->createHandler($response);

        $result = $middleware->process($request, $handler);

        $this->assertTrue($result->hasHeader('Cache-Control'));
        $this->assertStringContainsString('public', $result->getHeaderLine('Cache-Control'));
    }

    public function testReturns304WhenEtagMatches(): void
    {
        $middleware = new CacheMiddleware();
        $content = 'test content';

        // First request to get ETag
        $request1 = new ServerRequest('GET', '/test');
        $response1 = $this->createResponse($content);
        $handler1 = $this->createHandler($response1);
        $result1 = $middleware->process($request1, $handler1);
        $etag = $result1->getHeaderLine('ETag');

        // Second request with If-None-Match
        $request2 = (new ServerRequest('GET', '/test'))
            ->withHeader('If-None-Match', $etag);
        $response2 = $this->createResponse($content);
        $handler2 = $this->createHandler($response2);
        $result2 = $middleware->process($request2, $handler2);

        $this->assertSame(304, $result2->getStatusCode());
    }

    public function testDoesNotCache304Response(): void
    {
        $middleware = new CacheMiddleware();
        $request = new ServerRequest('GET', '/test');

        $response = (new Response())->withStatus(304);
        $handler = $this->createHandler($response);

        $result = $middleware->process($request, $handler);

        $this->assertFalse($result->hasHeader('ETag'));
    }

    public function testOnlyCachesGetAndHeadMethods(): void
    {
        $middleware = new CacheMiddleware();

        // POST should not be cached
        $postRequest = new ServerRequest('POST', '/test');
        $postResponse = $this->createResponse('test');
        $postHandler = $this->createHandler($postResponse);
        $postResult = $middleware->process($postRequest, $postHandler);

        $this->assertFalse($postResult->hasHeader('ETag'));

        // GET should be cached
        $getRequest = new ServerRequest('GET', '/test');
        $getResponse = $this->createResponse('test');
        $getHandler = $this->createHandler($getResponse);
        $getResult = $middleware->process($getRequest, $getHandler);

        $this->assertTrue($getResult->hasHeader('ETag'));
    }

    public function testDisabledMiddlewareDoesNotModifyResponse(): void
    {
        $middleware = new CacheMiddleware(enabled: false);
        $request = new ServerRequest('GET', '/test');
        $response = $this->createResponse('test');
        $handler = $this->createHandler($response);

        $result = $middleware->process($request, $handler);

        $this->assertFalse($result->hasHeader('ETag'));
        $this->assertFalse($result->hasHeader('Cache-Control'));
    }

    public function testGeneratesWeakEtagWhenConfigured(): void
    {
        $middleware = new CacheMiddleware(useWeakEtag: true);
        $request = new ServerRequest('GET', '/test');
        $response = $this->createResponse('test');
        $handler = $this->createHandler($response);

        $result = $middleware->process($request, $handler);

        $etag = $result->getHeaderLine('ETag');
        $this->assertStringStartsWith('W/"', $etag);
    }

    public function testHandlesMultipleEtagsInIfNoneMatch(): void
    {
        $middleware = new CacheMiddleware();
        $content = 'test content';

        // Get the actual ETag
        $request1 = new ServerRequest('GET', '/test');
        $response1 = $this->createResponse($content);
        $handler1 = $this->createHandler($response1);
        $result1 = $middleware->process($request1, $handler1);
        $actualEtag = $result1->getHeaderLine('ETag');

        // Request with multiple ETags including the actual one
        $request2 = (new ServerRequest('GET', '/test'))
            ->withHeader('If-None-Match', '"old-etag", ' . $actualEtag);
        $response2 = $this->createResponse($content);
        $handler2 = $this->createHandler($response2);
        $result2 = $middleware->process($request2, $handler2);

        $this->assertSame(304, $result2->getStatusCode());
    }

    public function testHandlesWildcardInIfNoneMatch(): void
    {
        $middleware = new CacheMiddleware();
        $request = (new ServerRequest('GET', '/test'))
            ->withHeader('If-None-Match', '*');
        $response = $this->createResponse('test');
        $handler = $this->createHandler($response);

        $result = $middleware->process($request, $handler);

        $this->assertSame(304, $result->getStatusCode());
    }

    public function testDoesNotOverwriteExistingCacheControl(): void
    {
        $cacheControl = CacheControlDirective::create()->public()->maxAge(3600);
        $middleware = new CacheMiddleware(cacheControl: $cacheControl);

        $request = new ServerRequest('GET', '/test');
        $response = $this->createResponse('test')
            ->withHeader('Cache-Control', 'private, max-age=60');
        $handler = $this->createHandler($response);

        $result = $middleware->process($request, $handler);

        $this->assertStringContainsString('private', $result->getHeaderLine('Cache-Control'));
        $this->assertStringNotContainsString('public', $result->getHeaderLine('Cache-Control'));
    }

    public function testDoesNotOverwriteExistingEtag(): void
    {
        $middleware = new CacheMiddleware();
        $request = new ServerRequest('GET', '/test');
        $response = $this->createResponse('test')
            ->withHeader('ETag', '"custom-etag"');
        $handler = $this->createHandler($response);

        $result = $middleware->process($request, $handler);

        $this->assertSame('"custom-etag"', $result->getHeaderLine('ETag'));
    }

    private function createResponse(string $content): ResponseInterface
    {
        $response = new Response();
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write($content);
        $body->rewind();

        return $response->withBody($body);
    }

    private function createHandler(ResponseInterface $response): RequestHandlerInterface
    {
        return new class ($response) implements RequestHandlerInterface {
            public function __construct(private ResponseInterface $response)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }
}
