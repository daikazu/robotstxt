<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RobotsTxtServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('robotstxt')
            ->hasRoute('web')
            ->hasConfigFile();
    }
}
