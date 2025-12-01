# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please report it responsibly.

### How to Report

1. **Do NOT** create a public GitHub issue for security vulnerabilities
2. Email the maintainer directly at: **methorz@spammerz.de**
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 7 days
- **Resolution Timeline**: Depends on severity (critical: ASAP, high: 30 days, medium: 90 days)

### After Resolution

- Security fixes will be released as patch versions
- Credit will be given to reporters (unless anonymity is requested)
- A security advisory will be published for significant vulnerabilities

## Security Best Practices

When using this package:

- **Keep dependencies updated** - Run `composer update` regularly
- **Use latest PHP version** - Security fixes are backported to supported versions only
- **Use private caching for sensitive data** - Never cache user-specific data publicly
- **Understand cache scope** - CDNs and proxies may cache public responses
- **Review caching headers** - Ensure sensitive endpoints don't get cached

## Known Security Considerations

### Private vs Public Caching

```php
// DANGEROUS: User-specific data with public caching
CacheControlDirective::create()->public(); // DON'T use for /api/user/profile

// SAFE: User-specific data with private caching
CacheControlDirective::create()->private(); // Good for /api/user/profile
```

### Sensitive Data Guidelines

| Endpoint Type | Recommended Directive |
|---------------|----------------------|
| Public API (product list) | `public, max-age=3600` |
| User profile | `private, max-age=300` |
| Authentication | `no-store` |
| Financial data | `no-store, no-cache` |
| Session-based | `private, no-cache` |

### ETag Security

- ETags are based on response body hash
- Don't include sensitive data that varies per user in cached responses
- Use weak ETags (`W/`) for semantically equivalent responses

### Cache Poisoning Prevention

- This middleware only handles validation (304 responses)
- Ensure upstream caches (CDNs) are properly configured
- Use `Vary` header for content negotiation

## Contact

- **Security Issues**: methorz@spammerz.de
- **General Issues**: [GitHub Issues](https://github.com/MethorZ/http-cache-middleware/issues)

---

Thank you for helping keep this project secure!

