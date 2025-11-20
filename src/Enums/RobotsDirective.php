<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt\Enums;

enum RobotsDirective: string
{
    case ALLOW = 'Allow';
    case DISALLOW = 'Disallow';
    case USER_AGENT = 'User-agent';
    case SITEMAP = 'Sitemap';
    case HOST = 'Host';
    case CONTENT_SIGNAL = 'Content-Signal';

    public function format(string $value): string
    {
        return "{$this->value}: {$value}";
    }
}
