<?php

declare(strict_types=1);

namespace MethorZ\HttpCache\Tests\Unit\Generator;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use MethorZ\HttpCache\Generator\ETagGenerator;
use PHPUnit\Framework\TestCase;

final class ETagGeneratorTest extends TestCase
{
    public function testGeneratesStrongEtag(): void
    {
        $response = new Response();
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write('test content');
        $response = $response->withBody($body);

        $etag = ETagGenerator::generate($response);

        $this->assertStringStartsWith('"', $etag);
        $this->assertStringEndsWith('"', $etag);
        $this->assertStringNotContainsString('W/', $etag);
    }

    public function testGeneratesWeakEtag(): void
    {
        $response = new Response();
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write('test content');
        $response = $response->withBody($body);

        $etag = ETagGenerator::generateWeak($response);

        $this->assertStringStartsWith('W/"', $etag);
        $this->assertStringEndsWith('"', $etag);
    }

    public function testGeneratesConsistentEtagForSameContent(): void
    {
        $content = 'identical content';

        $response1 = new Response();
        $body1 = new Stream(fopen('php://temp', 'r+'));
        $body1->write($content);
        $response1 = $response1->withBody($body1);

        $response2 = new Response();
        $body2 = new Stream(fopen('php://temp', 'r+'));
        $body2->write($content);
        $response2 = $response2->withBody($body2);

        $etag1 = ETagGenerator::generate($response1);
        $etag2 = ETagGenerator::generate($response2);

        $this->assertSame($etag1, $etag2);
    }

    public function testGeneratesDifferentEtagForDifferentContent(): void
    {
        $response1 = new Response();
        $body1 = new Stream(fopen('php://temp', 'r+'));
        $body1->write('content 1');
        $response1 = $response1->withBody($body1);

        $response2 = new Response();
        $body2 = new Stream(fopen('php://temp', 'r+'));
        $body2->write('content 2');
        $response2 = $response2->withBody($body2);

        $etag1 = ETagGenerator::generate($response1);
        $etag2 = ETagGenerator::generate($response2);

        $this->assertNotSame($etag1, $etag2);
    }

    public function testGeneratesWithDifferentAlgorithms(): void
    {
        $response = new Response();
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write('test');
        $response = $response->withBody($body);

        $md5Etag = ETagGenerator::generateWithAlgorithm($response, 'md5');
        $sha256Etag = ETagGenerator::generateWithAlgorithm($response, 'sha256');

        $this->assertNotSame($md5Etag, $sha256Etag);
    }

    public function testIdentifiesWeakEtag(): void
    {
        $this->assertTrue(ETagGenerator::isWeak('W/"abc123"'));
        $this->assertFalse(ETagGenerator::isWeak('"abc123"'));
    }

    public function testExtractsHashFromEtag(): void
    {
        $this->assertSame('abc123', ETagGenerator::extractHash('"abc123"'));
        $this->assertSame('abc123', ETagGenerator::extractHash('W/"abc123"'));
    }

    public function testMatchesEtagsWithWeakComparison(): void
    {
        $this->assertTrue(ETagGenerator::matches('"abc123"', '"abc123"', true));
        $this->assertTrue(ETagGenerator::matches('W/"abc123"', '"abc123"', true));
        $this->assertTrue(ETagGenerator::matches('"abc123"', 'W/"abc123"', true));
        $this->assertFalse(ETagGenerator::matches('"abc123"', '"def456"', true));
    }

    public function testMatchesEtagsWithStrongComparison(): void
    {
        $this->assertTrue(ETagGenerator::matches('"abc123"', '"abc123"', false));
        $this->assertFalse(ETagGenerator::matches('W/"abc123"', '"abc123"', false));
        $this->assertFalse(ETagGenerator::matches('"abc123"', 'W/"abc123"', false));
    }
}
