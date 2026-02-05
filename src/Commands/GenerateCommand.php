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
