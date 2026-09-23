<?php

use Illuminate\Support\Facades\Blade;

it('ships a Boost guideline that compiles as Blade', function (): void {
    $path = __DIR__ . '/../resources/boost/guidelines/core.blade.php';

    expect($path)->toBeFile();

    $rendered = Blade::render(file_get_contents($path));

    expect($rendered)->toContain('daikazu/robotstxt');
});

it('ships Boost skills with valid frontmatter', function (): void {
    $skills = glob(__DIR__ . '/../resources/boost/skills/*/SKILL.md');

    expect($skills)->not->toBeEmpty();

    foreach ($skills as $skill) {
        // Normalise CRLF (e.g. git checkouts on Windows) so line anchors match
        $contents = str_replace("\r\n", "\n", file_get_contents($skill));

        expect(preg_match('/\A---\n(.*?)\n---\n/s', $contents, $matches))->toBe(1, "Missing frontmatter in {$skill}");

        $frontmatter = $matches[1];

        // The skill name must match its directory name
        expect($frontmatter)->toMatch('/^name: ' . preg_quote(basename(dirname($skill)), '/') . '$/m')
            ->and($frontmatter)->toMatch('/^description: \S.+$/m');
    }
});
