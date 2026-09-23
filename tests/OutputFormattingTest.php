<?php

use Daikazu\Robotstxt\RobotsTxtManager;

it('renders the default production config without stray blank lines', function (): void {
    config()->set('app.env', 'production');
    config()->set('robotstxt.environments.production.custom_text', null);

    $output = (new RobotsTxtManager)->build();

    expect($output)->toBe([
        'Sitemap: http://localhost/sitemap.xml',
        '',
        'User-agent: *',
        'Allow: /',
    ]);
});

it('skips the global content signal block when every signal is null', function (): void {
    config()->set('app.env', 'testing');
    config()->set('robotstxt.environments.testing.paths', [
        '*' => ['allow' => ['/']],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', ['sitemap.xml']);
    config()->set('robotstxt.environments.testing.content_signals', [
        'search'   => null,
        'ai_input' => null,
        'ai_train' => null,
    ]);

    $output = (new RobotsTxtManager)->build();

    expect($output)->toBe([
        'Sitemap: http://localhost/sitemap.xml',
        '',
        'User-agent: *',
        'Allow: /',
    ]);
});
