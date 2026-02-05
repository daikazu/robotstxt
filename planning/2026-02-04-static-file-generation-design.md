# Static File Generation Design

## Problem

Laravel-focused Nginx configurations (Herd, Forge, etc.) include a default block that intercepts `/robots.txt` requests:

```nginx
location = /robots.txt { access_log off; log_not_found off; }
```

This serves static files directly, bypassing Laravel entirely. If no physical file exists, Nginx returns a 404. Users currently must modify their Nginx config to work around this, which is limiting for users without server access.

## Solution

Generate a static `public/robots.txt` file instead of serving dynamically. The file is auto-generated and gitignored, similar to how `vendor/` works.

## Commands

### `php artisan robots:install`

One-time setup command:

1. Publishes `config/robotstxt.php` if not present
2. Adds `public/robots.txt` to `.gitignore`
3. Adds composer hooks to `composer.json`:
   ```json
   "scripts": {
     "post-install-cmd": ["@php artisan robots:generate --quiet"],
     "post-update-cmd": ["@php artisan robots:generate --quiet"]
   }
   ```
4. Runs `robots:generate` immediately

### `php artisan robots:generate`

Generates the robots.txt file:

1. Builds content using existing `RobotsTxtManager`
2. Writes to `public/robots.txt`
3. Outputs success message (unless `--quiet` flag)

## Auto-regeneration

In non-production environments only:

- On each request, compare `filemtime(config_path('robotstxt.php'))` vs `filemtime(public_path('robots.txt'))`
- If config is newer (or file doesn't exist), regenerate silently
- Runs in `RobotsTxtServiceProvider::boot()`

Production environments only update via the `robots:generate` command (triggered by composer hooks during deploy).

## Architecture Changes

### Keep
- `RobotsTxtManager` - builds content (unchanged)
- `RobotsDirective` enum (unchanged)
- `config/robotstxt.php` structure (unchanged)

### Remove
- `RobotsTextController` - no longer needed
- `routes/web.php` - no longer needed
- Nginx documentation from README

### Add
- `Commands/InstallCommand.php`
- `Commands/GenerateCommand.php`
- Auto-regeneration logic in `RobotsTxtServiceProvider::boot()`

### Update
- `RobotsTxtServiceProvider` - remove route registration, add commands, add auto-regen
- `README.md` - simpler installation, remove Nginx section

## User Experience

### New Installation
```bash
composer require daikazu/robotstxt
php artisan robots:install
```

### Development Workflow
- Edit `config/robotstxt.php`
- File auto-regenerates on next request

### Deployment
- `composer install` triggers generation automatically
- Or run `php artisan robots:generate` in deploy script

### Upgrading Existing Users
- Run `php artisan robots:install`
- Remove custom Nginx config (no longer needed)
