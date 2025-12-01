# MethorZ HTTP Cache Middleware

**PSR-15 HTTP caching middleware with ETag support and RFC 7234 compliance**

[![CI](https://github.com/MethorZ/http-cache-middleware/actions/workflows/ci.yml/badge.svg)](https://github.com/MethorZ/http-cache-middleware/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/MethorZ/http-cache-middleware/graph/badge.svg)](https://codecov.io/gh/MethorZ/http-cache-middleware)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Automatic HTTP caching for PSR-15 applications with ETag generation, 304 Not Modified responses, and Cache-Control header management. Zero configuration, production-ready.

---

## ✨ Features

- 🏷️ **Automatic ETag Generation** - MD5/SHA256/custom algorithm support
- 🚀 **304 Not Modified** - Automatic conditional request handling
- 📋 **Cache-Control Builder** - Fluent interface for RFC 7234 directives
- ✅ **RFC Compliant** - RFC 7234 (caching) & RFC 7232 (conditional requests)
- 🎯 **Conditional Requests** - `If-None-Match`, wildcard support
- 💪 **Strong & Weak ETags** - Full support for both ETag types
- 🔧 **Zero Configuration** - Sensible defaults, works out-of-the-box
- 🎨 **Highly Customizable** - Control caching behavior per-route
- 📦 **Framework Agnostic** - Works with any PSR-15 application

---

## 📦 Installation

```bash
composer require methorz/http-cache-middleware
```

---

## 🚀 Quick Start

### **Basic Usage**

```php
use MethorZ\HttpCache\Middleware\CacheMiddleware;

// Add to middleware pipeline
$app->pipe(new CacheMiddleware());
```

That's it! All `GET` and `HEAD` requests will now have:
- Automatic ETag generation
- 304 Not Modified responses
- Proper caching headers

---

## 📖 Detailed Usage

### **With Cache-Control Directives**

```php
use MethorZ\HttpCache\Middleware\CacheMiddleware;
use MethorZ\HttpCache\Directive\CacheControlDirective;

$cacheControl = CacheControlDirective::create()
    ->public()
    ->maxAge(3600)
    ->mustRevalidate();

$middleware = new CacheMiddleware(cacheControl: $cacheControl);
```

**Generated Headers**:
```
ETag: "5d41402abc4b2a76b9719d911017c592"
Cache-Control: public, max-age=3600, must-revalidate
```

### **Configuration Options**

```php
$middleware = new CacheMiddleware(
    enabled: true,                  // Enable/disable caching
    cacheControl: $directive,       // Cache-Control directive
    useWeakEtag: false,            // Use weak ETags (W/)
    etagAlgorithm: 'md5',          // Hash algorithm (md5, sha256, etc.)
    cacheableMethods: ['GET', 'HEAD'], // Cacheable HTTP methods
    cacheableStatuses: [200, 203], // Cacheable status codes
);
```

### **Development Mode (Disable Caching)**

For development, you want fresh data on every request. Simply disable the middleware:

```php
// Option 1: Conditionally add middleware based on environment
if (getenv('APP_ENV') !== 'development') {
    $app->pipe(new CacheMiddleware());
}

// Option 2: Disable via constructor parameter
$middleware = new CacheMiddleware(
    enabled: getenv('APP_ENV') !== 'development'
);

// Option 3: Don't add middleware to pipeline in development
// (recommended - cleanest approach)
```

**Recommended approach**: Only add `CacheMiddleware` to your production pipeline configuration, not in development.

---

## 🎯 Cache-Control Directive Builder

Fluent interface for building Cache-Control headers:

### **Common Patterns**

**Public, cacheable for 1 hour**:
```php
CacheControlDirective::create()
    ->public()
    ->maxAge(3600)
    ->mustRevalidate();
// Output: "public, max-age=3600, must-revalidate"
```

**Private, no caching**:
```php
CacheControlDirective::create()
    ->private()
    ->noCache();
// Output: "private, no-cache"
```

**Immutable assets (images, CSS, JS)**:
```php
CacheControlDirective::create()
    ->public()
    ->maxAge(31536000) // 1 year
    ->immutable();
// Output: "public, max-age=31536000, immutable"
```

**API responses with shared cache**:
```php
CacheControlDirective::create()
    ->public()
    ->maxAge(300)      // Browser cache: 5 minutes
    ->sMaxAge(3600)    // CDN cache: 1 hour
    ->staleWhileRevalidate(60);
// Output: "public, max-age=300, s-maxage=3600, stale-while-revalidate=60"
```

### **All Directives**

| Method | Description | Example |
|--------|-------------|---------|
| `public()` | Cache may be stored by any cache | `public` |
| `private()` | Cache only for single user | `private` |
| `noCache()` | Must revalidate before use | `no-cache` |
| `noStore()` | Must not be stored anywhere | `no-store` |
| `maxAge(int)` | Maximum freshness time | `max-age=3600` |
| `sMaxAge(int)` | Shared cache max age | `s-maxage=7200` |
| `mustRevalidate()` | Must revalidate when stale | `must-revalidate` |
| `proxyRevalidate()` | Proxy must revalidate | `proxy-revalidate` |
| `noTransform()` | Cache must not transform response | `no-transform` |
| `staleWhileRevalidate(int)` | Serve stale while fetching fresh | `stale-while-revalidate=60` |
| `staleIfError(int)` | Serve stale if origin errors | `stale-if-error=120` |
| `immutable()` | Response will never change | `immutable` |

---

## 🏷️ ETag Generation

### **Automatic ETag Generation**

```php
use MethorZ\HttpCache\Generator\ETagGenerator;

// Strong ETag (exact match required)
$etag = ETagGenerator::generate($response);
// Output: "5d41402abc4b2a76b9719d911017c592"

// Weak ETag (semantic equality)
$weakEtag = ETagGenerator::generateWeak($response);
// Output: W/"5d41402abc4b2a76b9719d911017c592"

// Custom algorithm
$sha256Etag = ETagGenerator::generateWithAlgorithm($response, 'sha256');
```

### **ETag Utilities**

```php
// Check if ETag is weak
ETagGenerator::isWeak('W/"abc"'); // true
ETagGenerator::isWeak('"abc"');   // false

// Extract hash value
ETagGenerator::extractHash('"abc123"');   // "abc123"
ETagGenerator::extractHash('W/"abc123"'); // "abc123"

// Compare ETags
ETagGenerator::matches('"abc"', 'W/"abc"', weakComparison: true);  // true
ETagGenerator::matches('"abc"', 'W/"abc"', weakComparison: false); // false
```

---

## 🔄 How It Works

### **1. First Request (Cache Miss)**

```
Client → GET /api/items
Server → 200 OK
         ETag: "abc123"
         Cache-Control: public, max-age=3600
         Body: {...}
```

**Client caches response with ETag**

### **2. Subsequent Request (Cache Validation)**

```
Client → GET /api/items
         If-None-Match: "abc123"
Server → 304 Not Modified
         ETag: "abc123"
         Cache-Control: public, max-age=3600
         (empty body)
```

**Benefits**:
- ⚡ **Faster**: No body transmission (~95% bandwidth reduction)
- 💰 **Cheaper**: Reduced server CPU & network costs
- 🌍 **Better UX**: Instant responses for unchanged resources

---

## 🎯 Use Cases

### **1. Static Asset Caching**

```php
// For images, CSS, JS with content hashing in filename
$middleware = new CacheMiddleware(
    cacheControl: CacheControlDirective::create()
        ->public()
        ->maxAge(31536000) // 1 year
        ->immutable(),
);
```

### **2. API Response Caching**

```php
// Cache API responses for 5 minutes
$middleware = new CacheMiddleware(
    cacheControl: CacheControlDirective::create()
        ->public()
        ->maxAge(300)
        ->mustRevalidate(),
);
```

### **3. Dynamic Content with Validation**

```php
// Always validate with server, but use weak ETags
$middleware = new CacheMiddleware(
    useWeakEtag: true,
    cacheControl: CacheControlDirective::create()
        ->public()
        ->noCache() // Always revalidate
        ->maxAge(0),
);
```

### **4. Private User Data**

```php
// Cache in browser only, not in shared caches
$middleware = new CacheMiddleware(
    cacheControl: CacheControlDirective::create()
        ->private()
        ->maxAge(300),
);
```

### **5. CDN Integration**

```php
// Different cache times for browser vs CDN
$middleware = new CacheMiddleware(
    cacheControl: CacheControlDirective::create()
        ->public()
        ->maxAge(300)      // Browser: 5 minutes
        ->sMaxAge(3600)    // CDN: 1 hour
        ->staleWhileRevalidate(60),
);
```

---

## 🔧 Configuration Examples

### **Mezzio / Laminas**

```php
// config/autoload/middleware.global.php
use MethorZ\HttpCache\Middleware\CacheMiddleware;
use MethorZ\HttpCache\Directive\CacheControlDirective;

return [
    'dependencies' => [
        'factories' => [
            CacheMiddleware::class => function (): CacheMiddleware {
                return new CacheMiddleware(
                    cacheControl: CacheControlDirective::create()
                        ->public()
                        ->maxAge(3600),
                );
            },
        ],
    ],
];

// config/pipeline.php
$app->pipe(CacheMiddleware::class);
```

### **Per-Route Configuration**

```php
// Apply different caching strategies per route
$publicCaching = new CacheMiddleware(
    cacheControl: CacheControlDirective::create()->public()->maxAge(3600),
);

$privateCaching = new CacheMiddleware(
    cacheControl: CacheControlDirective::create()->private()->maxAge(300),
);

$app->get('/api/public', [$publicCaching, PublicHandler::class]);
$app->get('/api/user/profile', [$privateCaching, ProfileHandler::class]);
```

---

## 📊 HTTP Headers Reference

### **Request Headers (Client → Server)**

| Header | Description | Example |
|--------|-------------|---------|
| `If-None-Match` | Conditional request with ETag | `"abc123"` or `W/"abc123"` or `*` |
| `If-Modified-Since` | Conditional request with date | `Wed, 21 Oct 2015 07:28:00 GMT` |

### **Response Headers (Server → Client)**

| Header | Description | Example |
|--------|-------------|---------|
| `ETag` | Entity tag for resource version | `"abc123"` or `W/"abc123"` |
| `Cache-Control` | Caching directives | `public, max-age=3600` |
| `Expires` | Absolute expiration time | `Wed, 21 Oct 2025 07:28:00 GMT` |
| `Last-Modified` | Resource modification time | `Wed, 21 Oct 2024 07:28:00 GMT` |

---

## 🧪 Testing

```bash
# Run tests
composer test

# Static analysis
composer analyze

# Code style
composer cs-check
composer cs-fix
```

**Test Coverage**: 37 tests, 57 assertions, 100% passing

---

## ⚡ Performance Impact

### **Bandwidth Savings**

```
Without caching:
GET /api/items → 200 OK (10 KB body) → 10 KB transferred

With caching (subsequent requests):
GET /api/items (If-None-Match: "abc123") → 304 Not Modified → ~500 bytes transferred

Savings: ~95% bandwidth reduction
```

### **Server Load Reduction**

- ✅ 304 responses skip expensive body serialization
- ✅ ETag comparison is instant (simple hash check)
- ✅ Reduces database queries when responses haven't changed
- ✅ Lower CPU usage for repeated identical requests

---

## 🔒 Security Considerations

### **Private vs Public**

```php
// ❌ Don't cache sensitive user data publicly
CacheControlDirective::create()->public(); // Bad for /api/user/profile

// ✅ Use private for user-specific data
CacheControlDirective::create()->private(); // Good for /api/user/profile
```

### **Cache Invalidation**

This middleware handles **validation** (304 responses), not **invalidation**. For cache invalidation:
- Change resource content → new ETag → cache miss → fresh response
- Use `no-cache` directive → always revalidate with server
- Use CDN purge APIs for immediate invalidation

---

## 📚 Resources

- [RFC 7234: HTTP Caching](https://tools.ietf.org/html/rfc7234)
- [RFC 7232: Conditional Requests](https://tools.ietf.org/html/rfc7232)
- [MDN: HTTP Caching](https://developer.mozilla.org/en-US/docs/Web/HTTP/Caching)
- [MDN: Cache-Control](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control)

---

## 🔗 Related Packages

This package is part of the MethorZ HTTP middleware ecosystem:

| Package | Description |
|---------|-------------|
| **[methorz/http-dto](https://github.com/methorz/http-dto)** | Automatic HTTP ↔ DTO conversion with validation |
| **[methorz/http-problem-details](https://github.com/methorz/http-problem-details)** | RFC 7807 error handling middleware |
| **[methorz/http-cache-middleware](https://github.com/methorz/http-cache-middleware)** | HTTP caching with ETag support (this package) |
| **[methorz/http-request-logger](https://github.com/methorz/http-request-logger)** | Structured logging with request tracking |
| **[methorz/openapi-generator](https://github.com/methorz/openapi-generator)** | Automatic OpenAPI spec generation |

These packages work together seamlessly in PSR-15 applications.

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🤝 Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## 🔗 Links

- [Changelog](CHANGELOG.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)
- [Issues](https://github.com/MethorZ/http-cache-middleware/issues)

