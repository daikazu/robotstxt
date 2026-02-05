<?php

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    config()->set('app.env', 'testing');
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => ['/admin'],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);

    // Clean up any existing robots.txt
    if (File::exists(public_path('robots.txt'))) {
        File::delete(public_path('robots.txt'));
    }
});

afterEach(function (): void {
    if (File::exists(public_path('robots.txt'))) {
        File::delete(public_path('robots.txt'));
    }
});

it('generates robots.txt file in public directory', function (): void {
    $this->artisan('robots:generate')
        ->assertSuccessful();

    expect(File::exists(public_path('robots.txt')))->toBeTrue();

    $content = File::get(public_path('robots.txt'));
    expect($content)->toContain('User-agent: *')
        ->and($content)->toContain('Disallow: /admin')
        ->and($content)->toContain('Allow: /');
});

it('outputs success message', function (): void {
    $this->artisan('robots:generate')
        ->expectsOutputToContain('robots.txt')
        ->assertSuccessful();
});

it('runs quietly with --quiet flag', function (): void {
    $this->artisan('robots:generate --quiet')
        ->assertSuccessful();

    expect(File::exists(public_path('robots.txt')))->toBeTrue();
});
