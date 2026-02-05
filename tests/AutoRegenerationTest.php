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
