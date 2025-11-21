<picture>
   <source media="(prefers-color-scheme: dark)" srcset="art/header-dark.png">
   <img alt="Logo for ROBOTS.TXT" src="art/header-light.png">
</picture>


[![Latest Version on Packagist](https://img.shields.io/packagist/v/daikazu/robotstxt.svg?style=flat-square)](https://packagist.org/packages/daikazu/robotstxt)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/robotstxt/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daikazu/robotstxt/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/robotstxt/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/daikazu/robotstxt/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daikazu/robotstxt.svg?style=flat-square)](https://packagist.org/packages/daikazu/robotstxt)

# Dynamic robots.txt for your Laravel app.

A Laravel package for dynamically generating robots.txt files with environment-specific configurations. Control how search engines and AI crawlers interact with your site using modern features like Cloudflare's Content Signals Policy, per-environment rules, and flexible content signal directives.

Perfect for applications that need different robots.txt rules across environments (production, staging, local) or want granular control over AI training, search indexing, and content access.

## Features

- **Environment-Specific Configuration** - Different robots.txt rules for production, staging, local, etc.
- **Content Signals Support** - Implement Cloudflare's Content Signals Policy to control AI training, search indexing, and AI content usage
- **Flexible User-Agent Rules** - Define global rules or per-agent directives (disallow, allow, content signals)
- **Host Directive** - Specify preferred domain for crawlers
- **Sitemap Management** - Automatically include sitemap URLs
- **Custom Text** - Add custom content to your robots.txt file
- **Human-Readable Policies** - Optional policy comment blocks with custom or default text

## Installation

You can install the package via composer:

```bash
composer require daikazu/robotstxt
```
You can publish the config file with:

```bash
php artisan vendor:publish --tag="robotstxt-config"
```

### Nginx Configuration (Required for Production)

If you're getting a 404 status (but still seeing content), you need to configure Nginx to pass robots.txt requests to Laravel:

**For Laravel Herd:**
Add this to your site's Nginx config (via Herd UI or `~/.config/herd/Nginx/[site].conf`):

```nginx
location = /robots.txt {
      try_files $uri /index.php?$query_string;
      access_log off;
      log_not_found off;
  }
```

**For Laravel Forge/Vapor:**
Add the same location block to your Nginx configuration.

**For custom servers:**
Add to your server block in your Nginx config file.

Then restart Nginx/Herd.

## Usage

After installation, the package automatically registers a route at `/robots.txt` that serves your dynamically generated robots.txt file.

### Basic Configuration

The default configuration file (`config/robotstxt.php`) provides environment-specific settings. Here's a simple example:

```php
return [
    'environments' => [
        'production' => [
            'paths' => [
                '*' => [
                    'disallow' => ['/admin', '/api'],
                    'allow' => ['/'],
                ],
            ],
            'sitemaps' => [
                'sitemap.xml',
            ],
        ],
    ],
];
```

This generates:
```
Sitemap: https://example.com/sitemap.xml

User-agent: *
Disallow: /admin
Disallow: /api
Allow: /
```

### Content Signals (AI & Search Control)

Control how AI crawlers and search engines use your content with Cloudflare's Content Signals Policy:

```php
'production' => [
    // Enable human-readable policy comment block
    'content_signals_policy' => [
        'enabled' => true,
        'custom_policy' => null, // or provide your own HEREDOC text
    ],

    // Global content signals (applied at top level)
    'content_signals' => [
        'search'   => true,   // Allow search indexing
        'ai_input' => false,  // Block AI input/RAG
        'ai_train' => false,  // Block AI training
    ],

    'paths' => [
        '*' => [
            'disallow' => [],
            'allow' => ['/'],
        ],
    ],
],
```

Generates:
```
# As a condition of accessing this website, you agree to abide by the following
# content signals:
# [Full policy text...]

Content-Signal: search=yes, ai-input=no, ai-train=no

User-agent: *
Allow: /
```

### Per-Agent Content Signals

You can also define content signals for specific user agents:

```php
'paths' => [
    '*' => [
        'disallow' => [],
        'allow' => ['/'],
    ],
    'Googlebot' => [
        'content_signals' => [
            'search'   => true,
            'ai_input' => true,
            'ai_train' => false,
        ],
        'disallow' => ['/private'],
        'allow' => ['/'],
    ],
],
```

Generates:
```
Content-Signal: search=yes, ai-input=no, ai-train=no

User-agent: *
Allow: /

User-agent: Googlebot
Content-Signal: search=yes, ai-input=yes, ai-train=no
Disallow: /private
Allow: /
```

### Host Directive

Specify the preferred domain for crawlers:

```php
'production' => [
    'host' => 'https://www.example.com',
    // ... other config
],
```

Generates:
```
Host: https://www.example.com
```

### Custom Text

Add arbitrary custom content to the end of your robots.txt:

```php
'production' => [
    'custom_text' => <<<'TEXT'
# Custom crawl-delay for specific bots
User-agent: Bingbot
Crawl-delay: 1
TEXT,
    // ... other config
],
```

### Environment-Specific Rules

Define different rules for each environment:

```php
return [
    'environments' => [
        'production' => [
            'paths' => [
                '*' => [
                    'disallow' => [],
                    'allow' => ['/'],
                ],
            ],
            'content_signals' => [
                'search' => true,
                'ai_input' => false,
                'ai_train' => false,
            ],
        ],
        'staging' => [
            'paths' => [
                '*' => [
                    'disallow' => ['/'],
                ],
            ],
        ],
        'local' => [
            'paths' => [
                '*' => [
                    'disallow' => ['/'],
                ],
            ],
        ],
    ],
];
```

### Content Signal Values

- `true` or `'yes'` - Permission granted
- `false` or `'no'` - Permission denied
- `null` - No preference specified (signal not included)

### Content Signal Types

- **search** - Building a search index and providing search results (excludes AI summaries)
- **ai_input** - Inputting content into AI models (RAG, grounding, AI Overviews)
- **ai_train** - Training or fine-tuning AI models

### Complete Configuration Example

```php
return [
    'environments' => [
        'production' => [
            // Content Signals Policy
            'content_signals_policy' => [
                'enabled' => true,
                'custom_policy' => null,
            ],

            // Global Content Signals
            'content_signals' => [
                'search'   => true,
                'ai_input' => false,
                'ai_train' => false,
            ],

            // User-Agent Rules
            'paths' => [
                '*' => [
                    'disallow' => ['/admin', '/api'],
                    'allow' => ['/'],
                ],
                'Googlebot' => [
                    'content_signals' => [
                        'search'   => true,
                        'ai_input' => true,
                        'ai_train' => false,
                    ],
                    'disallow' => [],
                    'allow' => ['/'],
                ],
            ],

            // Sitemaps
            'sitemaps' => [
                'sitemap.xml',
                'sitemap-news.xml',
            ],

            // Host
            'host' => 'https://www.example.com',

            // Custom Text
            'custom_text' => <<<'TEXT'
# Additional custom directives
User-agent: Bingbot
Crawl-delay: 1
TEXT,
        ],
    ],
];
```

## Testing

```bash
composer test
```

## Roadmap

- [ ] **Crawl-delay Directive** - Add native support for crawl-delay configuration per user-agent

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mike Wall](https://github.com/daikazu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
