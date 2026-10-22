# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-10-22

### Changed
- PHP requirement raised to `~8.4.27 || ~8.5.12` (releases patched for php2shell: CVE-2026-109618, CVE-2026-109619). PHP 8.2 and 8.3 are no longer supported.
- Updated development dependencies: phpunit/phpunit ^13.4, phpstan/phpstan ^2.3, slevomat/coding-standard ^8.31, squizlabs/php_codesniffer ^4.0.1.

## [1.0.0] - 2025-11-27

### Added
- Initial release of HTTP Cache Middleware
- Automatic ETag generation (MD5, SHA256, custom algorithms)
- 304 Not Modified response handling
- Cache-Control header builder with fluent interface
- RFC 7234 (HTTP caching) compliance
- RFC 7232 (conditional requests) compliance
- Support for conditional requests (If-None-Match)
- Strong and weak ETag support
- Wildcard ETag matching
- Per-route cache configuration
- PSR-15 middleware implementation
- Zero-configuration default setup
- Comprehensive test suite (37 tests)
- Complete documentation with examples

[1.1.0]: https://github.com/methorz/http-cache-middleware/releases/tag/v1.1.0
[1.0.0]: https://github.com/methorz/http-cache-middleware/releases/tag/v1.0.0

