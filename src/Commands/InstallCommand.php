<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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

        /** @var array<string, mixed>|null $composer */
        $composer = json_decode(File::get($composerPath), true);

        if (! is_array($composer)) {
            $this->warn('Could not parse composer.json, skipping hook setup.');

            return;
        }

        if (! isset($composer['scripts']) || ! is_array($composer['scripts'])) {
            $composer['scripts'] = [];
        }

        /** @var array<string, mixed> $scripts */
        $scripts = $composer['scripts'];

        $hook = '@php artisan robots:generate --quiet';
        $modified = false;

        foreach (['post-install-cmd', 'post-update-cmd'] as $event) {
            if (! isset($scripts[$event])) {
                $scripts[$event] = [];
            }

            if (! is_array($scripts[$event])) {
                $scripts[$event] = [$scripts[$event]];
            }

            /** @var array<int, string> $eventScripts */
            $eventScripts = $scripts[$event];

            if (! in_array($hook, $eventScripts, true)) {
                $scripts[$event][] = $hook;
                $modified = true;
            }
        }

        $composer['scripts'] = $scripts;

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
