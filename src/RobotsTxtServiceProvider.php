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
        // Use a terminating callback to check and regenerate after each request
        // This ensures we have the latest config values
        $this->app->terminating(function (): void {
            $this->autoRegenerateInNonProduction();
        });
    }

    private function autoRegenerateInNonProduction(): void
    {
        // Only auto-regenerate in non-production environments
        // Use config() directly to get the current environment value (important for tests)
        if (config('app.env') === 'production') {
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
