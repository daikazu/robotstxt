---
name: robotstxt-configuration
description: Configure the daikazu/robotstxt package - per-environment robots.txt rules, user-agent allow/disallow paths, sitemaps, Cloudflare Content Signals (search, ai-input, ai-train), blocking AI crawlers, and troubleshooting when /robots.txt shows the wrong content or 404s.
---

# robots.txt Configuration (daikazu/robotstxt)

## When to use this skill

Use this skill when changing what `/robots.txt` returns, blocking or allowing crawlers (including AI crawlers), adding sitemaps, setting content signals, or debugging why `/robots.txt` doesn't reflect the config.

## How it works

- `GET /robots.txt` (route name `robots.txt`) is handled by the package and returns `text/plain`.
- Output is built from `config('robotstxt.environments.' . config('app.env'))`.
- Publish the config with `php artisan vendor:publish --tag="robotstxt-config"`. A published config **replaces** the package defaults entirely.
- If the current environment isn't configured, or has no `paths`, the output is `User-agent: *` / `Disallow: /`.

## Configuration reference

Every key is optional.

```php
// config/robotstxt.php
return [
    'environments' => [
        'production' => [
            // Human-readable policy comment block, printed once at the top
            'content_signals_policy' => [
                'enabled'       => false, // accepts bools and loose values like 1, "true", "on"
                'custom_policy' => null,  // string; each line is prefixed with "# "
            ],

            // Global content signals: added to every User-agent group without its own
            'content_signals' => [
                'search'   => true,  // true|'yes', false|'no', null = omitted
                'ai_input' => false,
                'ai_train' => false,
            ],

            // One entry per User-agent; key is the agent name
            'paths' => [
                '*' => [
                    'disallow' => ['/admin', '/api'],
                    'allow'    => ['/'],
                ],
                'GPTBot' => [
                    'disallow' => ['/'],
                ],
            ],

            'sitemaps'    => ['sitemap.xml'], // relative paths are made absolute with url()
            'host'        => null,            // e.g. 'https://www.example.com'
            'custom_text' => null,            // raw text appended at the end (HEREDOC recommended)
        ],

        'staging' => [
            'paths' => ['*' => ['disallow' => ['/']]],
        ],
    ],
];
```

Output order: Sitemaps, Host, policy comments, User-agent groups, custom text. Sections are separated by one blank line.

## Content signals

- Config keys use underscores (`ai_input`); output uses hyphens (`ai-input`).
- `Content-Signal` lines are always written inside a User-agent group, right after the `User-agent:` line.
- A per-agent `content_signals` block **replaces** the global signals for that agent. It doesn't merge with them.
- To opt an agent out of the global signals, give it a `content_signals` block with every value set to `null`.

```php
'content_signals' => ['search' => true, 'ai_input' => false, 'ai_train' => false],
'paths' => [
    '*' => ['allow' => ['/']],
    'Googlebot' => [
        'content_signals' => ['search' => true, 'ai_input' => true, 'ai_train' => false],
        'allow' => ['/'],
    ],
],
```

Produces:

```
User-agent: *
Content-Signal: search=yes, ai-input=no, ai-train=no
Allow: /

User-agent: Googlebot
Content-Signal: search=yes, ai-input=yes, ai-train=no
Allow: /
```

## Common tasks

- **Block AI training but allow search:** set the global `content_signals` to `search => true, ai_train => false`. To block known AI crawlers outright as well, add a `paths` entry per agent (e.g. `GPTBot`, `ClaudeBot`, `CCBot`, `Google-Extended`) with `'disallow' => ['/']`.
- **Hide a non-production environment:** leave it out of the config, or set `'paths' => ['*' => ['disallow' => ['/']]]`.
- **Directives the config doesn't support** (e.g. `Crawl-delay`): put them in `custom_text`.

## Troubleshooting

- **Old or static content is served:** delete `public/robots.txt`. The web server serves it before the request reaches Laravel.
- **404 status on Nginx (Forge, Herd):** the server block usually has a `location = /robots.txt` that only serves static files. Change it to:

```nginx
location = /robots.txt {
    try_files $uri /index.php?$query_string;
    access_log off;
    log_not_found off;
}
```

- **Changes not showing:** run `php artisan config:clear`, since a cached config won't pick up edits to `config/robotstxt.php`.
- **Wrong environment's rules:** check `APP_ENV`. The environment key must match it exactly.

## Testing

```php
it('serves the production robots.txt', function () {
    config()->set('app.env', 'production');

    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('User-agent: *')
        ->assertSee('Disallow: /admin');
});
```
