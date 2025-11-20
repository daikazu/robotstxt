<?php

use Daikazu\Robotstxt\RobotsTxtManager;

beforeEach(function (): void {
    config()->set('app.env', 'testing');
});

it('renders global content signals at top level', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);
    config()->set('robotstxt.environments.testing.content_signals', [
        'search'   => 'yes',
        'ai_input' => 'no',
        'ai_train' => 'no',
    ]);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    // Global content signals should appear before user-agent blocks
    $signalIndex = null;
    $userAgentIndex = null;

    foreach ($output as $index => $line) {
        if (str_starts_with($line, 'Content-Signal:')) {
            $signalIndex = $index;
        }
        if (str_starts_with($line, 'User-agent:')) {
            $userAgentIndex = $index;
            break;
        }
    }

    expect($signalIndex)->not->toBeNull()
        ->and($userAgentIndex)->not->toBeNull()
        ->and($signalIndex)->toBeLessThan($userAgentIndex)
        ->and($output)->toContain('Content-Signal: search=yes, ai-input=no, ai-train=no');
});

it('uses per-agent content signals when specified', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'content_signals' => [
                'search'   => true,
                'ai_input' => false,
                'ai_train' => false,
            ],
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    expect($output)->toContain('User-agent: *')
        ->and($output)->toContain('Content-Signal: search=yes, ai-input=no, ai-train=no')
        ->and($output)->toContain('Allow: /');
});

it('global and per-agent signals are independent', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'content_signals' => [
                'search'   => 'yes',
                'ai_input' => 'yes',
                'ai_train' => 'no',
            ],
            'disallow' => [],
            'allow'    => ['/'],
        ],
        'Googlebot' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);
    config()->set('robotstxt.environments.testing.content_signals', [
        'search'   => 'no',
        'ai_input' => 'no',
        'ai_train' => 'yes',
    ]);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    // Global signals should appear at the top
    expect($output)->toContain('Content-Signal: search=no, ai-input=no, ai-train=yes');

    // First agent has per-agent signals (appears after User-agent: *)
    $starIndex = array_search('User-agent: *', $output);
    $foundPerAgentSignal = false;
    for ($i = $starIndex + 1; $i < count($output); $i++) {
        if (str_starts_with($output[$i], 'Content-Signal:')) {
            expect($output[$i])->toBe('Content-Signal: search=yes, ai-input=yes, ai-train=no');
            $foundPerAgentSignal = true;
            break;
        }
        if (str_starts_with($output[$i], 'User-agent:')) {
            break;
        }
    }
    expect($foundPerAgentSignal)->toBeTrue();

    // Second agent (Googlebot) should NOT have any content signals
    $googlebotIndex = array_search('User-agent: Googlebot', $output);
    $foundGooglebotSignal = false;
    for ($i = $googlebotIndex + 1; $i < count($output); $i++) {
        if (str_starts_with($output[$i], 'Content-Signal:')) {
            $foundGooglebotSignal = true;
            break;
        }
        if (str_starts_with($output[$i], 'User-agent:')) {
            break;
        }
    }
    expect($foundGooglebotSignal)->toBeFalse();
});

it('shows policy before global content signals when enabled', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
        'Googlebot' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', ['sitemap.xml']);
    config()->set('robotstxt.environments.testing.content_signals_policy', [
        'enabled'       => true,
        'custom_policy' => null,
    ]);
    config()->set('robotstxt.environments.testing.content_signals', [
        'search'   => 'yes',
        'ai_input' => 'no',
        'ai_train' => 'no',
    ]);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    // Policy should appear once (for global signals)
    $policyCount = 0;
    foreach ($output as $line) {
        if (str_contains($line, '# As a condition of accessing this website')) {
            $policyCount++;
        }
    }

    expect($policyCount)->toBe(1);
});

it('shows policy only once at top when both global and per-agent signals exist', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
        'Googlebot' => [
            'content_signals' => [
                'search'   => 'no',
                'ai_input' => 'no',
                'ai_train' => 'no',
            ],
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);
    config()->set('robotstxt.environments.testing.content_signals_policy', [
        'enabled'       => true,
        'custom_policy' => null,
    ]);
    config()->set('robotstxt.environments.testing.content_signals', [
        'search'   => 'yes',
        'ai_input' => 'yes',
        'ai_train' => 'yes',
    ]);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    // Policy should appear only once at the top
    $policyCount = 0;
    $firstPolicyIndex = null;
    foreach ($output as $index => $line) {
        if (str_contains($line, '# As a condition of accessing this website')) {
            $policyCount++;
            if ($firstPolicyIndex === null) {
                $firstPolicyIndex = $index;
            }
        }
    }

    // Find first User-agent line
    $firstUserAgentIndex = null;
    foreach ($output as $index => $line) {
        if (str_starts_with($line, 'User-agent:')) {
            $firstUserAgentIndex = $index;
            break;
        }
    }

    expect($policyCount)->toBe(1)
        ->and($firstPolicyIndex)->not->toBeNull()
        ->and($firstUserAgentIndex)->not->toBeNull()
        ->and($firstPolicyIndex)->toBeLessThan($firstUserAgentIndex);
});

it('uses custom policy when provided', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);
    config()->set('robotstxt.environments.testing.content_signals_policy', [
        'enabled'       => true,
        'custom_policy' => "Custom Policy Line 1\nCustom Policy Line 2",
    ]);
    config()->set('robotstxt.environments.testing.content_signals', [
        'search' => 'yes',
    ]);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    expect($output)->toContain('# Custom Policy Line 1')
        ->and($output)->toContain('# Custom Policy Line 2')
        ->and($output)->not->toContain('# As a condition of accessing this website');
});

it('converts boolean true to yes and false to no', function (): void {
    config()->set('robotstxt.environments.testing.paths', [
        '*' => [
            'content_signals' => [
                'search'   => true,
                'ai_input' => false,
                'ai_train' => true,
            ],
            'disallow' => [],
            'allow'    => ['/'],
        ],
    ]);
    config()->set('robotstxt.environments.testing.sitemaps', []);

    $manager = new RobotsTxtManager;
    $output = $manager->build();

    expect($output)->toContain('Content-Signal: search=yes, ai-input=no, ai-train=yes');
});
