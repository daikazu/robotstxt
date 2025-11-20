<?php

use Daikazu\Robotstxt\RobotsTxtManager;

beforeEach(function (): void {
    config()->set('app.env', 'testing');
});

it('includes custom text at the end when provided', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', ['sitemap.xml']);

    $customText = <<<'TEXT'
# Custom crawl-delay for specific bots
User-agent: Bingbot
Crawl-delay: 1
TEXT;

    config()->set('robotstxt.environments.testing.custom_text', $customText);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    expect($output)->toContain('# Custom crawl-delay for specific bots')
        ->and($output)->toContain('User-agent: Bingbot')
        ->and($output)->toContain('Crawl-delay: 1');
});

it('adds blank lines between sitemaps and user-agent blocks', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', ['sitemap.xml']);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    $sitemapIndex = null;
    $blankLineIndex = null;
    $userAgentIndex = null;

    foreach ($output as $index => $line) {
        if (str_starts_with($line, 'Sitemap:')) {
            $sitemapIndex = $index;
        }
        if ($sitemapIndex !== null && $line === '' && $blankLineIndex === null) {
            $blankLineIndex = $index;
        }
        if (str_starts_with($line, 'User-agent:')) {
            $userAgentIndex = $index;
            break;
        }
    }

    expect($sitemapIndex)->not->toBeNull()
        ->and($blankLineIndex)->not->toBeNull()
        ->and($userAgentIndex)->not->toBeNull()
        ->and($blankLineIndex)->toBe($sitemapIndex + 1)
        ->and($userAgentIndex)->toBe($blankLineIndex + 1);
});

it('adds blank lines between multiple user-agent blocks', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
        'Googlebot' => [
            'disallow' => ['/private'],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    $firstAllowIndex = null;
    $blankLineIndex = null;
    $secondUserAgentIndex = null;

    foreach ($output as $index => $line) {
        if ($line === 'Allow: /' && $firstAllowIndex === null) {
            $firstAllowIndex = $index;
        }
        if ($firstAllowIndex !== null && $line === '' && $blankLineIndex === null) {
            $blankLineIndex = $index;
        }
        if ($line === 'User-agent: Googlebot') {
            $secondUserAgentIndex = $index;
            break;
        }
    }

    expect($firstAllowIndex)->not->toBeNull()
        ->and($blankLineIndex)->not->toBeNull()
        ->and($secondUserAgentIndex)->not->toBeNull()
        ->and($blankLineIndex)->toBe($firstAllowIndex + 1)
        ->and($secondUserAgentIndex)->toBe($blankLineIndex + 1);
});

it('adds blank line before custom text', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);
    config()->set('robotstxt.environments.testing.custom_text', '# Custom text');

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    $allowIndex = array_search('Allow: /', $output);
    $blankIndex = null;
    $customIndex = null;

    for ($i = $allowIndex + 1; $i < count($output); $i++) {
        if ($output[$i] === '' && $blankIndex === null) {
            $blankIndex = $i;
        }
        if (str_starts_with($output[$i], '# Custom text')) {
            $customIndex = $i;
            break;
        }
    }

    expect($blankIndex)->not->toBeNull()
        ->and($customIndex)->not->toBeNull()
        ->and($blankIndex)->toBe($allowIndex + 1)
        ->and($customIndex)->toBe($blankIndex + 1);
});
