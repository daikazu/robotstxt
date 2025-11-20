<?php

// config for Daikazu/Robotstxt
return [
    'environments' => [
        'production' => [
            // Content Signals Policy Configuration
            'content_signals_policy' => [
                // Whether to include the human-readable policy comment block (shown once at top)
                'enabled' => false,
                // Optional: Custom policy text (use HEREDOC for multi-line)
                // If null, uses the default Cloudflare Content Signals Policy
                'custom_policy' => null,
            ],
            // Global Content Signals (applied to all user-agents unless overridden per-agent)
            // Set to null to disable global signals
            'content_signals' => [
                'search'   => null,    // Building a search index and providing search results
                'ai_input' => null,    // Inputting content into one or more AI models
                'ai_train' => null,    // Training or fine-tuning AI models
            ],
            'paths' => [
                '*' => [
                    'disallow' => [],
                    'allow'    => [],
                ],
            ],
            'sitemaps' => [
                'sitemap.xml',
            ],
            // Optional: Host directive (specifies the preferred domain for crawlers)
            // Example: 'https://example.com' or 'https://www.example.com'
            'host' => null,
            // Optional: Custom text to include in robots.txt (use HEREDOC for multi-line)
            // This text will be added at the end of the robots.txt file
            // Lines starting with # will be treated as comments
            'custom_text' => <<<'TEXT'
#    .__________________________.
#    | .___________________. |==|
#    | | ................. | |  |
#    | | ::[ Dear robot ]: | |  |
#    | | ::::[ be nice ]:: | |  |
#    | | ::::::::::::::::: | |  |
#    | | ::::::::::::::::: | |  |
#    | | ::::::::::::::::: | |  |
#    | | ::::::::::::::::: | | ,|
#    | !___________________! |(c|
#    !_______________________!__!
#   /                            \
#  /  [][][][][][][][][][][][][]  \
# /  [][][][][][][][][][][][][][]  \
#(  [][][][][____________][][][][]  )
# \ ------------------------------ /
#  \______________________________/
TEXT,
        ],
    ],
];
