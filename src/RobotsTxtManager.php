<?php

declare(strict_types=1);

namespace Daikazu\Robotstxt;

use Daikazu\Robotstxt\Enums\RobotsDirective;

final class RobotsTxtManager
{
    /**
     * @readonly
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    private string $currentEnvironment {
        get {
            $env = config('app.env');

            return is_string($env) ? $env : '';
        }
    }

    /**
     * @readonly
     *
     * @var array<array-key, mixed>
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    private array $definedPaths {
        get {
            $paths = config("robotstxt.environments.{$this->currentEnvironment}.paths", []);

            return is_array($paths) ? $paths : [];
        }
    }

    /**
     * @readonly
     *
     * @var array<int, string>
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    private array $definedSitemaps {
        get {
            // Tolerate a single string and ignore non-string entries
            $sitemaps = (array) config("robotstxt.environments.{$this->currentEnvironment}.sitemaps", []);

            return array_values(array_filter($sitemaps, is_string(...)));
        }
    }

    /**
     * @readonly
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    private bool $contentSignalsPolicyEnabled {
        get {
            $enabled = config("robotstxt.environments.{$this->currentEnvironment}.content_signals_policy.enabled", false);

            // Accept loose values like 1, "true" or "on" (e.g. from env()) instead of failing the request
            return filter_var($enabled, FILTER_VALIDATE_BOOL);
        }
    }

    /**
     * @readonly
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    private ?string $customPolicy {
        get {
            $policy = config("robotstxt.environments.{$this->currentEnvironment}.content_signals_policy.custom_policy");

            return $policy === null || is_string($policy) ? $policy : null;
        }
    }

    /**
     * @readonly
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    private ?string $customText {
        get {
            $text = config("robotstxt.environments.{$this->currentEnvironment}.custom_text");

            return $text === null || is_string($text) ? $text : null;
        }
    }

    /**
     * @readonly
     *
     * @var array<array-key, mixed>|null
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    private ?array $globalContentSignals {
        get {
            $signals = config("robotstxt.environments.{$this->currentEnvironment}.content_signals");

            return is_array($signals) ? $signals : null;
        }
    }

    /**
     * @readonly
     *
     * @noinspection PhpPropertyOnlyWrittenInspection
     */
    private ?string $host {
        get {
            $host = config("robotstxt.environments.{$this->currentEnvironment}.host");

            return $host === null || is_string($host) ? $host : null;
        }
    }

    /**
     * Build the array containing all the entries for the txt file.
     *
     * @return array<int, string>
     */
    public function build(): array
    {
        $output = [];

        // Add sitemaps first
        if ($this->definedSitemaps !== []) {
            $output = [...$output, ...$this->getSitemaps()];
        }

        // Add host directive (if defined)
        if ($this->host !== null) {
            // Add blank line before host if we have sitemaps
            if ($output !== []) {
                $output[] = '';
            }

            $output[] = RobotsDirective::HOST->format($this->host);
        }

        // Add policy at the top (if enabled)
        // This appears once at the top regardless of where content signals are defined
        if ($this->contentSignalsPolicyEnabled) {
            // Add blank line before policy if we have sitemaps
            if ($output !== []) {
                $output[] = '';
            }

            $output = [...$output, ...$this->getContentSignalPolicy()];
        }

        // Add paths (user-agent blocks)
        $paths = $this->definedPaths !== [] ? $this->getPaths() : $this->defaultRobot();

        // Add blank line before paths if we have content above
        if ($output !== [] && $paths !== []) {
            $output[] = '';
        }

        $output = [...$output, ...$paths];

        // Add custom text at the end
        if ($this->customText !== null) {
            $customLines = explode("\n", $this->customText);

            // Add blank line before custom text
            if ($output !== []) {
                $output[] = '';
            }

            $output = [...$output, ...$customLines];
        }

        return $output;
    }

    /**
     * Returns 'Disallow /' as the default for every robot
     *
     * @return array<int, string>
     */
    private function defaultRobot(): array
    {
        $entries = [RobotsDirective::USER_AGENT->format('*')];

        $contentSignalDirective = $this->getContentSignalDirectiveForAgent(null);
        if ($contentSignalDirective !== null) {
            $entries[] = $contentSignalDirective;
        }

        $entries[] = RobotsDirective::DISALLOW->format('/');

        return $entries;
    }

    /**
     * Assemble all the defined paths from the config.
     *
     * Loop through all the defined paths,
     * creating an array which matches the order of the path entries in the txt file
     *
     * @return array<int, string>
     */
    private function getPaths(): array
    {
        // For each user agent, get the user agent name and the paths for the agent,
        // adding them to the array
        $entries = [];
        $isFirstAgent = true;

        foreach ($this->definedPaths as $agent => $paths) {
            // Add blank line between user-agent blocks (except before the first one)
            if (! $isFirstAgent) {
                $entries[] = '';
            }
            $isFirstAgent = false;

            $entries[] = RobotsDirective::USER_AGENT->format((string) $agent);

            // Add content signal directive for this agent if configured
            $contentSignalDirective = $this->getContentSignalDirectiveForAgent($paths);
            if ($contentSignalDirective !== null) {
                $entries[] = $contentSignalDirective;
            }

            $entries = [...$entries, ...$this->parsePaths(RobotsDirective::DISALLOW, $paths)];
            $entries = [...$entries, ...$this->parsePaths(RobotsDirective::ALLOW, $paths)];
        }

        return $entries;
    }

    /**
     * Parse defined paths into sitemap entries
     *
     * @param  RobotsDirective  $directive  The directive (DISALLOW/ALLOW)
     * @param  mixed  $paths  Array of all the paths
     * @return array<int, string> Array containing the sitemap entries
     */
    private function parsePaths(RobotsDirective $directive, mixed $paths): array
    {
        if (! is_array($paths)) {
            return [];
        }

        $entries = [];
        $directiveKey = strtolower($directive->value);

        if (! array_key_exists($directiveKey, $paths) || ! is_array($paths[$directiveKey])) {
            return [];
        }

        foreach ($paths[$directiveKey] as $path) {
            if (is_string($path)) {
                $entries[] = $directive->format($path);
            }
        }

        return $entries;
    }

    /**
     * Assemble all the defined sitemaps from the config.
     *
     * Loop through all the defined sitemaps,
     * creating an array which matches the order of the sitemap entries in the txt file
     *
     * @return array<int, string>
     */
    private function getSitemaps(): array
    {
        $entries = [];

        foreach ($this->definedSitemaps as $sitemap) {
            // Sitemaps should always use a absolute url.
            // Combining the sitemap paths with Laravel's url() function will do nicely.
            /** @var string $url */
            $url = url($sitemap);
            $entries[] = RobotsDirective::SITEMAP->format($url);
        }

        return $entries;
    }

    /**
     * Get the human-readable content signals policy comment block.
     *
     * Returns either a custom policy or the default Cloudflare Content Signals Policy.
     * Each line is automatically prefixed with "# ".
     *
     * @return array<int, string> Array of comment lines
     */
    private function getContentSignalPolicy(): array
    {
        // Use custom policy if provided
        if ($this->customPolicy !== null) {
            return $this->formatPolicyAsComments($this->customPolicy);
        }

        // Default Cloudflare Content Signals Policy (already formatted with # prefixes)
        $policy = <<<'POLICY'
# As a condition of accessing this website, you agree to abide by the following
# content signals:

# (a)  If a content-signal = yes, you may collect content for the corresponding
#      use.
# (b)  If a content-signal = no, you may not collect content for the
#      corresponding use.
# (c)  If the website operator does not include a content signal for a
#      corresponding use, the website operator neither grants nor restricts
#      permission via content signal with respect to the corresponding use.

# The content signals and their meanings are:

# search:   building a search index and providing search results (e.g., returning
#           hyperlinks and short excerpts from your website's contents). Search does not
#           include providing AI-generated search summaries.
# ai-input: inputting content into one or more AI models (e.g., retrieval
#           augmented generation, grounding, or other real-time taking of content for
#           generative AI search answers).
# ai-train: training or fine-tuning AI models.

# ANY RESTRICTIONS EXPRESSED VIA CONTENT SIGNALS ARE EXPRESS RESERVATIONS OF
# RIGHTS UNDER ARTICLE 4 OF THE EUROPEAN UNION DIRECTIVE 2019/790 ON COPYRIGHT
# AND RELATED RIGHTS IN THE DIGITAL SINGLE MARKET.
POLICY;

        return explode("\n", $policy);
    }

    /**
     * Format policy text as comment lines.
     *
     * Splits the policy text by line breaks and prefixes each line with "# ".
     *
     * @param  string  $policy  The policy text
     * @return array<int, string> Array of comment lines
     */
    private function formatPolicyAsComments(string $policy): array
    {
        $lines = explode("\n", $policy);

        return array_map(fn (string $line): string => '# ' . $line, $lines);
    }

    /**
     * Build the machine-readable Content-Signal directive for a specific agent.
     *
     * Per-agent signals replace the global signals entirely. Agents without their own
     * signals inherit the global ones, since robots.txt rules outside a User-agent
     * group are ignored by crawlers (RFC 9309).
     *
     * @param  mixed  $agentPaths  The paths configuration for this agent
     * @return string|null The Content-Signal directive line, or null if no signals configured
     */
    private function getContentSignalDirectiveForAgent(mixed $agentPaths): ?string
    {
        $contentSignals = is_array($agentPaths) && is_array($agentPaths['content_signals'] ?? null)
            ? $agentPaths['content_signals']
            : $this->globalContentSignals;

        if ($contentSignals === null) {
            return null;
        }

        $signals = [];

        foreach ($contentSignals as $key => $value) {
            // Only include signals that are explicitly set (skips null and non-scalar values)
            if (is_scalar($value)) {
                // Convert underscore to hyphen (ai_input -> ai-input)
                $signalName = str_replace('_', '-', (string) $key);

                // Convert boolean true to 'yes', false to 'no'
                $signalValue = match ($value) {
                    true    => 'yes',
                    false   => 'no',
                    default => (string) $value,
                };

                $signals[] = $signalName . '=' . $signalValue;
            }
        }

        if ($signals === []) {
            return null;
        }

        return RobotsDirective::CONTENT_SIGNAL->format(implode(', ', $signals));
    }
}
