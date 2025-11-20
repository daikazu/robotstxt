<?php

use Daikazu\Robotstxt\RobotsTxtManager;

beforeEach(function (): void {
    config()->set('app.env', 'testing');
});

it('includes host directive when provided', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);
    config()->set('robotstxt.environments.testing.host', 'https://example.com');

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    expect($output)->toContain('Host: https://example.com');
});

it('does not include host directive when null', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);
    config()->set('robotstxt.environments.testing.host', null);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    $hasHost = false;
    foreach ($output as $line) {
        if (str_starts_with($line, 'Host:')) {
            $hasHost = true;
            break;
        }
    }

    expect($hasHost)->toBeFalse();
});

it('places host directive after sitemaps', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', ['sitemap.xml']);
    config()->set('robotstxt.environments.testing.host', 'https://www.example.com');

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    $sitemapIndex = null;
    $hostIndex = null;

    foreach ($output as $index => $line) {
        if (str_starts_with($line, 'Sitemap:')) {
            $sitemapIndex = $index;
        }
        if (str_starts_with($line, 'Host:')) {
            $hostIndex = $index;
        }
    }

    expect($sitemapIndex)->not->toBeNull()
        ->and($hostIndex)->not->toBeNull()
        ->and($hostIndex)->toBeGreaterThan($sitemapIndex);
});

it('places host directive before user-agent blocks', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);
    config()->set('robotstxt.environments.testing.host', 'https://example.com');

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    $hostIndex = null;
    $userAgentIndex = null;

    foreach ($output as $index => $line) {
        if (str_starts_with($line, 'Host:')) {
            $hostIndex = $index;
        }
        if (str_starts_with($line, 'User-agent:')) {
            $userAgentIndex = $index;
            break;
        }
    }

    expect($hostIndex)->not->toBeNull()
        ->and($userAgentIndex)->not->toBeNull()
        ->and($hostIndex)->toBeLessThan($userAgentIndex);
});

it('adds blank line between host and sitemaps', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', ['sitemap.xml']);
    config()->set('robotstxt.environments.testing.host', 'https://example.com');

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    $sitemapIndex = null;
    $blankLineIndex = null;
    $hostIndex = null;

    foreach ($output as $index => $line) {
        if (str_starts_with($line, 'Sitemap:')) {
            $sitemapIndex = $index;
        }
        if ($sitemapIndex !== null && $line === '' && $blankLineIndex === null) {
            $blankLineIndex = $index;
        }
        if (str_starts_with($line, 'Host:')) {
            $hostIndex = $index;
            break;
        }
    }

    expect($sitemapIndex)->not->toBeNull()
        ->and($blankLineIndex)->not->toBeNull()
        ->and($hostIndex)->not->toBeNull()
        ->and($blankLineIndex)->toBe($sitemapIndex + 1)
        ->and($hostIndex)->toBe($blankLineIndex + 1);
});
