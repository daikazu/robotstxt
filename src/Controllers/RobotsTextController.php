<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt\Controllers;

use Daikazu\Robotstxt\RobotsTxtManager;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class RobotsTextController extends Controller
{
    public function __construct(
        private readonly RobotsTxtManager $manager
    ) {}

    public function __invoke(): Response
    {
        $robots = implode(PHP_EOL, $this->manager->build());

        return response($robots, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
