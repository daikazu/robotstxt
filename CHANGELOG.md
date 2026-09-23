# Changelog

All notable changes to `robotstxt` will be documented in this file.

## v1.2.0 - 2026-09-23

### What's changed

#### Behaviour changes

- **Global content signals are now written inside each `User-agent` group** instead of on a standalone line at the top. Under RFC 9309, rules outside a group are ignored, so crawlers could drop the global signals. Every group without its own `content_signals` (including the default disallow-all group) now inherits the global ones. Per-agent signals still replace the global ones completely, and an all-`null` per-agent block opts that agent out.
- The default config's `*` group now outputs `Allow: /` instead of an empty `User-agent: *` group.

#### Fixes

- No more double blank line when every global `content_signals` value is `null` (the default config).
- `content_signals_policy.enabled` accepts loose values such as `1`, `"true"` or `"on"` (e.g. from `env()`). Before, these caused a 500 error on `/robots.txt`.
- Malformed config no longer causes a 500 error: non-array `paths`/`content_signals` fall back safely, a single `sitemaps` string is accepted, and non-scalar signal values are skipped.
- Custom text and policy with CRLF line endings no longer leave a trailing `\r` on each line.
- Lines are joined with `\n` instead of `PHP_EOL`, so output is the same on Windows servers.

#### Docs

- README: deleting Laravel's default `public/robots.txt` is now a required install step. Without it, the web server serves the static file and this package's output never appears. Forge/Vapor guidance is also corrected.

#### Maintenance

- CI now tests PHP 8.4/8.5 × Laravel 11/12/13 on Ubuntu and Windows (before this, every run failed).
- New tests for the HTTP route, config handling and output formatting.

#### Upgrading

If you rely on global `content_signals` and only want them on some agents, give the other agents their own `content_signals` block (all `null` to opt out).

**Full Changelog**: https://github.com/daikazu/robotstxt/compare/v1.1.0...v1.2.0

## v1.1.0 - 2026-03-18

Added Laravel 13 compatibility.
