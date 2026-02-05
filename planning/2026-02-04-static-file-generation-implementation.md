# Static File Generation Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace dynamic route with static `public/robots.txt` file generation to eliminate Nginx configuration requirements.

**Architecture:** Two artisan commands (`robots:install` for one-time setup, `robots:generate` for file creation). Auto-regeneration in non-production via ServiceProvider boot. Remove controller and routes entirely.

**Tech Stack:** Laravel Artisan Commands, Spatie Laravel Package Tools, Pest for testing

---

## Task 1: Create GenerateCommand

**Files:**
- Create: `src/Commands/GenerateCommand.php`
- Test: `tests/GenerateCommandTest.php`

**Step 1: Write the failing test**

Create `tests/GenerateCommandTest.php`:

```php
<?php

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    config()->set('app.env', 'testing');
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => ['/admin'],
            'allow' => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);

    // Clean up any existing robots.txt
    if (File::exists(public_path('robots.txt'))) {
        File::delete(public_path('robots.txt'));
    }
});

afterEach(function (): void {
    if (File::exists(public_path('robots.txt'))) {
        File::delete(public_path('robots.txt'));
    }
});

it('generates robots.txt file in public directory', function (): void {
    $this->artisan('robots:generate')
        ->assertSuccessful();

    expect(File::exists(public_path('robots.txt')))->toBeTrue();

    $content = File::get(public_path('robots.txt'));
    expect($content)->toContain('User-agent: *')
        ->and($content)->toContain('Disallow: /admin')
        ->and($content)->toContain('Allow: /');
});

it('outputs success message', function (): void {
    $this->artisan('robots:generate')
        ->expectsOutputToContain('robots.txt')
        ->assertSuccessful();
});

it('runs quietly with --quiet flag', function (): void {
    $this->artisan('robots:generate --quiet')
        ->assertSuccessful();

    expect(File::exists(public_path('robots.txt')))->toBeTrue();
});
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/GenerateCommandTest.php`
Expected: FAIL - command not found

**Step 3: Write the GenerateCommand**

Create `src/Commands/GenerateCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt\Commands;

use Daikazu\Robotstxt\RobotsTxtManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateCommand extends Command
{
    protected $signature = 'robots:generate';

    protected $description = 'Generate the robots.txt file in the public directory';

    public function handle(RobotsTxtManager $manager): int
    {
        $content = implode(PHP_EOL, $manager->build());

        File::put(public_path('robots.txt'), $content);

        $this->info('Generated public/robots.txt successfully.');

        return self::SUCCESS;
    }
}
```

**Step 4: Register command in ServiceProvider**

Modify `src/RobotsTxtServiceProvider.php` - add command registration:

```php
<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt;

use Daikazu\Robotstxt\Commands\GenerateCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RobotsTxtServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('robotstxt')
            ->hasRoute('web')
            ->hasConfigFile()
            ->hasCommand(GenerateCommand::class);
    }
}
```

**Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/GenerateCommandTest.php`
Expected: PASS

**Step 6: Run full test suite**

Run: `./vendor/bin/pest`
Expected: All tests PASS

**Step 7: Commit**

```bash
git add src/Commands/GenerateCommand.php tests/GenerateCommandTest.php src/RobotsTxtServiceProvider.php
git commit -m "feat: add robots:generate command"
```

---

## Task 2: Create InstallCommand

**Files:**
- Create: `src/Commands/InstallCommand.php`
- Test: `tests/InstallCommandTest.php`

**Step 1: Write the failing test**

Create `tests/InstallCommandTest.php`:

```php
<?php

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    config()->set('app.env', 'testing');
    config()->set('robotstxt.environments.testing.paths', [
        '*' => ['disallow' => [], 'allow' => ['/']],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);
});

afterEach(function (): void {
    // Clean up robots.txt
    if (File::exists(public_path('robots.txt'))) {
        File::delete(public_path('robots.txt'));
    }

    // Restore original .gitignore if we modified it
    $gitignore = base_path('.gitignore');
    if (File::exists($gitignore)) {
        $content = File::get($gitignore);
        $content = str_replace("\npublic/robots.txt\n", "\n", $content);
        $content = str_replace("\n/public/robots.txt\n", "\n", $content);
        File::put($gitignore, $content);
    }
});

it('adds robots.txt to gitignore', function (): void {
    $gitignore = base_path('.gitignore');
    $originalContent = File::exists($gitignore) ? File::get($gitignore) : '';

    // Ensure it doesn't already contain the entry
    if (str_contains($originalContent, 'public/robots.txt')) {
        $originalContent = str_replace("/public/robots.txt\n", '', $originalContent);
        $originalContent = str_replace("public/robots.txt\n", '', $originalContent);
        File::put($gitignore, $originalContent);
    }

    $this->artisan('robots:install')
        ->assertSuccessful();

    $content = File::get($gitignore);
    expect($content)->toContain('public/robots.txt');
});

it('does not duplicate gitignore entry if already present', function (): void {
    $gitignore = base_path('.gitignore');
    $originalContent = File::exists($gitignore) ? File::get($gitignore) : '';

    // Add the entry first
    if (! str_contains($originalContent, 'public/robots.txt')) {
        File::put($gitignore, $originalContent . "\n/public/robots.txt\n");
    }

    $this->artisan('robots:install')
        ->assertSuccessful();

    $content = File::get($gitignore);
    $count = substr_count($content, 'public/robots.txt');
    expect($count)->toBe(1);
});

it('generates robots.txt file', function (): void {
    $this->artisan('robots:install')
        ->assertSuccessful();

    expect(File::exists(public_path('robots.txt')))->toBeTrue();
});
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/InstallCommandTest.php`
Expected: FAIL - command not found

**Step 3: Write the InstallCommand**

Create `src/Commands/InstallCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;

class InstallCommand extends Command
{
    protected $signature = 'robots:install';

    protected $description = 'Install the robotstxt package: publish config, update .gitignore, add composer hooks, and generate robots.txt';

    public function handle(): int
    {
        $this->info('Installing robotstxt package...');

        // 1. Publish config if not exists
        $this->publishConfig();

        // 2. Add to .gitignore
        $this->updateGitignore();

        // 3. Add composer hooks
        $this->updateComposerJson();

        // 4. Generate robots.txt
        $this->call('robots:generate');

        $this->newLine();
        $this->info('robotstxt installed successfully!');

        return self::SUCCESS;
    }

    private function publishConfig(): void
    {
        $configPath = config_path('robotstxt.php');

        if (File::exists($configPath)) {
            $this->line('Config file already exists, skipping publish.');

            return;
        }

        $this->call('vendor:publish', [
            '--tag' => 'robotstxt-config',
        ]);
    }

    private function updateGitignore(): void
    {
        $gitignorePath = base_path('.gitignore');
        $entry = '/public/robots.txt';

        if (! File::exists($gitignorePath)) {
            File::put($gitignorePath, $entry . PHP_EOL);
            $this->line('Created .gitignore with robots.txt entry.');

            return;
        }

        $content = File::get($gitignorePath);

        // Check if already present (with or without leading slash)
        if (str_contains($content, 'public/robots.txt')) {
            $this->line('.gitignore already contains robots.txt entry.');

            return;
        }

        // Append entry
        $content = rtrim($content) . PHP_EOL . $entry . PHP_EOL;
        File::put($gitignorePath, $content);
        $this->line('Added robots.txt to .gitignore.');
    }

    private function updateComposerJson(): void
    {
        $composerPath = base_path('composer.json');

        if (! File::exists($composerPath)) {
            $this->warn('composer.json not found, skipping hook setup.');

            return;
        }

        $composer = json_decode(File::get($composerPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn('Could not parse composer.json, skipping hook setup.');

            return;
        }

        $hook = '@php artisan robots:generate --quiet';
        $modified = false;

        foreach (['post-install-cmd', 'post-update-cmd'] as $event) {
            if (! isset($composer['scripts'][$event])) {
                $composer['scripts'][$event] = [];
            }

            if (! is_array($composer['scripts'][$event])) {
                $composer['scripts'][$event] = [$composer['scripts'][$event]];
            }

            if (! in_array($hook, $composer['scripts'][$event], true)) {
                $composer['scripts'][$event][] = $hook;
                $modified = true;
            }
        }

        if ($modified) {
            File::put(
                $composerPath,
                json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
            );
            $this->line('Added composer hooks for robots:generate.');
        } else {
            $this->line('Composer hooks already configured.');
        }
    }
}
```

**Step 4: Register command in ServiceProvider**

Modify `src/RobotsTxtServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt;

use Daikazu\Robotstxt\Commands\GenerateCommand;
use Daikazu\Robotstxt\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RobotsTxtServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('robotstxt')
            ->hasRoute('web')
            ->hasConfigFile()
            ->hasCommand(GenerateCommand::class)
            ->hasCommand(InstallCommand::class);
    }
}
```

**Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/InstallCommandTest.php`
Expected: PASS

**Step 6: Run full test suite**

Run: `./vendor/bin/pest`
Expected: All tests PASS

**Step 7: Commit**

```bash
git add src/Commands/InstallCommand.php tests/InstallCommandTest.php src/RobotsTxtServiceProvider.php
git commit -m "feat: add robots:install command"
```

---

## Task 3: Add Auto-regeneration in Non-production

**Files:**
- Modify: `src/RobotsTxtServiceProvider.php`
- Test: `tests/AutoRegenerationTest.php`

**Step 1: Write the failing test**

Create `tests/AutoRegenerationTest.php`:

```php
<?php

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    config()->set('app.env', 'testing');
    config()->set('robotstxt.environments.testing.paths', [
        '*' => ['disallow' => [], 'allow' => ['/']],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);

    // Clean up
    if (File::exists(public_path('robots.txt'))) {
        File::delete(public_path('robots.txt'));
    }
});

afterEach(function (): void {
    if (File::exists(public_path('robots.txt'))) {
        File::delete(public_path('robots.txt'));
    }
});

it('auto-generates robots.txt in non-production if file does not exist', function (): void {
    config()->set('app.env', 'local');

    expect(File::exists(public_path('robots.txt')))->toBeFalse();

    // Trigger boot by making a request
    $this->get('/');

    expect(File::exists(public_path('robots.txt')))->toBeTrue();
});

it('does not auto-generate in production', function (): void {
    config()->set('app.env', 'production');
    config()->set('robotstxt.environments.production.paths', [
        '*' => ['disallow' => [], 'allow' => ['/']],
    ]);

    expect(File::exists(public_path('robots.txt')))->toBeFalse();

    // Trigger boot by making a request
    $this->get('/');

    expect(File::exists(public_path('robots.txt')))->toBeFalse();
});

it('regenerates when config is newer than robots.txt', function (): void {
    config()->set('app.env', 'local');

    // Create an old robots.txt
    File::put(public_path('robots.txt'), 'old content');
    touch(public_path('robots.txt'), time() - 3600); // 1 hour ago

    // Make config file newer (simulate by touching)
    $configPath = config_path('robotstxt.php');
    if (File::exists($configPath)) {
        touch($configPath, time());
    }

    // Trigger boot
    $this->get('/');

    $content = File::get(public_path('robots.txt'));
    expect($content)->not->toBe('old content')
        ->and($content)->toContain('User-agent: *');
});
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/AutoRegenerationTest.php`
Expected: FAIL - no auto-regeneration happening

**Step 3: Add auto-regeneration to ServiceProvider**

Modify `src/RobotsTxtServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt;

use Daikazu\Robotstxt\Commands\GenerateCommand;
use Daikazu\Robotstxt\Commands\InstallCommand;
use Illuminate\Support\Facades\File;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RobotsTxtServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('robotstxt')
            ->hasRoute('web')
            ->hasConfigFile()
            ->hasCommand(GenerateCommand::class)
            ->hasCommand(InstallCommand::class);
    }

    public function packageBooted(): void
    {
        $this->autoRegenerateInNonProduction();
    }

    private function autoRegenerateInNonProduction(): void
    {
        // Only auto-regenerate in non-production environments
        if ($this->app->environment('production')) {
            return;
        }

        $robotsPath = public_path('robots.txt');
        $configPath = config_path('robotstxt.php');

        // If robots.txt doesn't exist, generate it
        if (! File::exists($robotsPath)) {
            $this->generateRobotsTxt();

            return;
        }

        // If config exists and is newer than robots.txt, regenerate
        if (File::exists($configPath)) {
            $robotsTime = File::lastModified($robotsPath);
            $configTime = File::lastModified($configPath);

            if ($configTime > $robotsTime) {
                $this->generateRobotsTxt();
            }
        }
    }

    private function generateRobotsTxt(): void
    {
        $manager = $this->app->make(RobotsTxtManager::class);
        $content = implode(PHP_EOL, $manager->build());
        File::put(public_path('robots.txt'), $content);
    }
}
```

**Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/AutoRegenerationTest.php`
Expected: PASS

**Step 5: Run full test suite**

Run: `./vendor/bin/pest`
Expected: All tests PASS

**Step 6: Commit**

```bash
git add src/RobotsTxtServiceProvider.php tests/AutoRegenerationTest.php
git commit -m "feat: add auto-regeneration in non-production environments"
```

---

## Task 4: Remove Controller and Routes

**Files:**
- Delete: `src/Controllers/RobotsTextController.php`
- Delete: `routes/web.php`
- Modify: `src/RobotsTxtServiceProvider.php` (remove route registration)

**Step 1: Update ServiceProvider to remove route**

Modify `src/RobotsTxtServiceProvider.php` - remove `->hasRoute('web')`:

```php
<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt;

use Daikazu\Robotstxt\Commands\GenerateCommand;
use Daikazu\Robotstxt\Commands\InstallCommand;
use Illuminate\Support\Facades\File;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RobotsTxtServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('robotstxt')
            ->hasConfigFile()
            ->hasCommand(GenerateCommand::class)
            ->hasCommand(InstallCommand::class);
    }

    public function packageBooted(): void
    {
        $this->autoRegenerateInNonProduction();
    }

    private function autoRegenerateInNonProduction(): void
    {
        if ($this->app->environment('production')) {
            return;
        }

        $robotsPath = public_path('robots.txt');
        $configPath = config_path('robotstxt.php');

        if (! File::exists($robotsPath)) {
            $this->generateRobotsTxt();

            return;
        }

        if (File::exists($configPath)) {
            $robotsTime = File::lastModified($robotsPath);
            $configTime = File::lastModified($configPath);

            if ($configTime > $robotsTime) {
                $this->generateRobotsTxt();
            }
        }
    }

    private function generateRobotsTxt(): void
    {
        $manager = $this->app->make(RobotsTxtManager::class);
        $content = implode(PHP_EOL, $manager->build());
        File::put(public_path('robots.txt'), $content);
    }
}
```

**Step 2: Delete controller file**

Run: `rm src/Controllers/RobotsTextController.php`

**Step 3: Delete routes file**

Run: `rm routes/web.php`

**Step 4: Remove Controllers directory if empty**

Run: `rmdir src/Controllers`

**Step 5: Run full test suite**

Run: `./vendor/bin/pest`
Expected: All tests PASS

**Step 6: Run PHPStan**

Run: `composer analyse`
Expected: No errors

**Step 7: Commit**

```bash
git add -A
git commit -m "refactor: remove dynamic route in favor of static file generation"
```

---

## Task 5: Update README

**Files:**
- Modify: `README.md`

**Step 1: Update README with new installation instructions**

Replace the Installation and Nginx sections in `README.md`:

```markdown
## Installation

Install the package via composer:

```bash
composer require daikazu/robotstxt
```

Run the install command to set up the package:

```bash
php artisan robots:install
```

This command will:
- Publish the config file (`config/robotstxt.php`)
- Add `public/robots.txt` to your `.gitignore`
- Add composer hooks to auto-generate on `composer install/update`
- Generate the initial `robots.txt` file

That's it! No web server configuration required.

## How It Works

The package generates a static `public/robots.txt` file that is served directly by your web server (Nginx, Apache, etc.). This approach:

- Works with all web servers without configuration
- Provides better performance (no PHP execution per request)
- Automatically regenerates in development when config changes
- Regenerates on deployment via composer hooks

### Development

In non-production environments, the package automatically regenerates `public/robots.txt` when your config file changes.

### Production

In production, the file is regenerated automatically during `composer install` or `composer update`. You can also manually regenerate:

```bash
php artisan robots:generate
```
```

**Step 2: Remove the Nginx Configuration section entirely**

Delete lines 41-62 (the entire "### Nginx Configuration" section).

**Step 3: Update the Usage section**

Change line 64-66 from:
```markdown
## Usage

After installation, the package automatically registers a route at `/robots.txt` that serves your dynamically generated robots.txt file.
```

To:
```markdown
## Usage

After installation, your `public/robots.txt` file is automatically generated and maintained.
```

**Step 4: Commit**

```bash
git add README.md
git commit -m "docs: update README for static file generation approach"
```

---

## Task 6: Update CLAUDE.md and Changelog

**Files:**
- Modify: `CLAUDE.md`
- Modify: `CHANGELOG.md`

**Step 1: Update CLAUDE.md**

Add new commands section:

```markdown
## Commands

```bash
composer test                    # Run tests (Pest)
composer analyse                 # Run PHPStan (level max)
composer format                  # Format code with Pint
php artisan robots:install       # One-time setup (config, gitignore, composer hooks)
php artisan robots:generate      # Generate public/robots.txt
```
```

Remove the controller from architecture section since it no longer exists.

**Step 2: Update CHANGELOG.md**

Add entry for this version:

```markdown
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
```

**Step 3: Commit**

```bash
git add CLAUDE.md CHANGELOG.md
git commit -m "docs: update CLAUDE.md and CHANGELOG for v2"
```

---

## Task 7: Final Verification

**Step 1: Run full test suite**

Run: `./vendor/bin/pest`
Expected: All tests PASS

**Step 2: Run PHPStan**

Run: `composer analyse`
Expected: No errors

**Step 3: Run Pint**

Run: `composer format`
Expected: Code formatted

**Step 4: Test install flow manually**

```bash
# Clean up
rm -f public/robots.txt

# Run install
php artisan robots:install

# Verify file exists
cat public/robots.txt
```

Expected: robots.txt content is displayed

**Step 5: Final commit if any formatting changes**

```bash
git add -A
git commit -m "chore: format code"
```
