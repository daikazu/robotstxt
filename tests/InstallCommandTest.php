<?php

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    config()->set('app.env', 'testing');
    config()->set('robotstxt.environments.testing.paths', [
        '*' => ['disallow' => [], 'allow' => ['/']],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);
});

afterEach(function (): void {
    // Clean up robots.txt
    if (File::exists(public_path('robots.txt'))) {
        File::delete(public_path('robots.txt'));
    }

    // Restore original .gitignore if we modified it
    $gitignore = base_path('.gitignore');
    if (File::exists($gitignore)) {
        $content = File::get($gitignore);
        $content = str_replace("\npublic/robots.txt\n", "\n", $content);
        $content = str_replace("\n/public/robots.txt\n", "\n", $content);
        File::put($gitignore, $content);
    }
});

it('adds robots.txt to gitignore', function (): void {
    $gitignore = base_path('.gitignore');
    $originalContent = File::exists($gitignore) ? File::get($gitignore) : '';

    // Ensure it doesn't already contain the entry
    if (str_contains($originalContent, 'public/robots.txt')) {
        $originalContent = str_replace("/public/robots.txt\n", '', $originalContent);
        $originalContent = str_replace("public/robots.txt\n", '', $originalContent);
        File::put($gitignore, $originalContent);
    }

    $this->artisan('robots:install')
        ->assertSuccessful();

    $content = File::get($gitignore);
    expect($content)->toContain('public/robots.txt');
});

it('does not duplicate gitignore entry if already present', function (): void {
    $gitignore = base_path('.gitignore');
    $originalContent = File::exists($gitignore) ? File::get($gitignore) : '';

    // Add the entry first
    if (! str_contains($originalContent, 'public/robots.txt')) {
        File::put($gitignore, $originalContent . "\n/public/robots.txt\n");
    }

    $this->artisan('robots:install')
        ->assertSuccessful();

    $content = File::get($gitignore);
    $count = substr_count($content, 'public/robots.txt');
    expect($count)->toBe(1);
});

it('generates robots.txt file', function (): void {
    $this->artisan('robots:install')
        ->assertSuccessful();

    expect(File::exists(public_path('robots.txt')))->toBeTrue();
});

it('removes bare robots.txt entries from gitignore', function (): void {
    $gitignore = base_path('.gitignore');
    $originalContent = File::exists($gitignore) ? File::get($gitignore) : '';

    // Add a bare robots.txt entry
    File::put($gitignore, $originalContent . "\nrobots.txt\n");

    $this->artisan('robots:install')
        ->assertSuccessful();

    $content = File::get($gitignore);
    expect($content)->not->toContain("\nrobots.txt\n")
        ->and($content)->toContain('public/robots.txt');
});
