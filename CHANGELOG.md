# Changelog

All notable changes to `robotstxt` will be documented in this file.

## [Unreleased]

### Changed
- Replaced dynamic route with static file generation
- `public/robots.txt` is now generated and served directly by the web server
- No Nginx/Apache configuration required

### Added
- `robots:install` command for one-time package setup
- `robots:generate` command to regenerate robots.txt
- Auto-regeneration in non-production when config changes
- Composer hooks for automatic regeneration on install/update

### Removed
- Dynamic `/robots.txt` route
- `RobotsTextController`
