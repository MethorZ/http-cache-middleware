<?php

declare(strict_types=1);

namespace MethorZ\HttpCache\Generator;

use Psr\Http\Message\ResponseInterface;

use function hash;
use function hash_final;
use function hash_init;
use function hash_update;
use function md5;

/**
 * Generates ETags for HTTP responses
 *
 * Supports both strong and weak ETags according to RFC 7232.
 * Uses streaming to avoid loading entire response body into memory.
 *
 * Usage:
 * ```php
 * $etag = ETagGenerator::generate($response);
 * $weakEtag = ETagGenerator::generateWeak($response);
 * ```
 */
final class ETagGenerator
{
    /**
     * Chunk size for streaming hash computation (8KB)
     */
    private const CHUNK_SIZE = 8192;

    /**
     * Generate a strong ETag from response body using streaming
     *
     * Strong ETags indicate byte-for-byte equality.
     * Uses streaming to avoid memory issues with large responses.
     *
     * @throws \RuntimeException
     */
    public static function generate(ResponseInterface $response): string
    {
        $hash = self::computeStreamingHash($response, 'md5');

        return '"' . $hash . '"';
    }

    /**
     * Generate a weak ETag from response body using streaming
     *
     * Weak ETags indicate semantic equality (W/ prefix).
     * Uses streaming to avoid memory issues with large responses.
     *
     * @throws \RuntimeException
     */
    public static function generateWeak(ResponseInterface $response): string
    {
        $hash = self::computeStreamingHash($response, 'md5');

        return 'W/"' . $hash . '"';
    }

    /**
     * Generate ETag using specified algorithm with streaming
     *
     * @param string $algorithm Hash algorithm (md5, sha256, sha512, etc.)
     *
     * @throws \RuntimeException
     */
    public static function generateWithAlgorithm(
        ResponseInterface $response,
        string $algorithm = 'md5',
        bool $weak = false,
    ): string {
        $hash = self::computeStreamingHash($response, $algorithm);

        return $weak ? 'W/"' . $hash . '"' : '"' . $hash . '"';
    }

    /**
     * Compute hash using streaming to avoid loading entire body into memory
     *
     * Reads response body in chunks (8KB) and computes hash incrementally.
     * Memory usage remains constant regardless of response size.
     *
     * @throws \RuntimeException
     */
    private static function computeStreamingHash(
        ResponseInterface $response,
        string $algorithm,
    ): string {
        $body = $response->getBody();
        $body->rewind();

        $context = hash_init($algorithm);

        while (!$body->eof()) {
            $chunk = $body->read(self::CHUNK_SIZE);
            hash_update($context, $chunk);
        }

        $body->rewind(); // Reset stream for subsequent use

        return hash_final($context);
    }

    /**
     * Check if an ETag is weak
     */
    public static function isWeak(string $etag): bool
    {
        return str_starts_with($etag, 'W/');
    }

    /**
     * Extract the hash value from an ETag
     */
    public static function extractHash(string $etag): string
    {
        // Remove W/ prefix if present
        $etag = str_starts_with($etag, 'W/') ? substr($etag, 2) : $etag;

        // Remove quotes
        return trim($etag, '"');
    }

    /**
     * Compare two ETags for equality
     *
     * Weak comparison allows matching weak and strong ETags
     */
    public static function matches(string $etag1, string $etag2, bool $weakComparison = true): bool
    {
        if ($weakComparison) {
            // Weak comparison: compare hash values only
            return self::extractHash($etag1) === self::extractHash($etag2);
        }

        // Strong comparison: exact match including weak prefix
        return $etag1 === $etag2;
    }
}
