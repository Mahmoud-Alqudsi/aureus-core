<?php

declare(strict_types=1);

namespace Scripts\Architecture;

class HookHandler
{
    protected Synchronizer $synchronizer;

    protected string $basePath;

    public function __construct(?Synchronizer $synchronizer = null)
    {
        $this->synchronizer = $synchronizer ?? new Synchronizer;
        $this->basePath = dirname(__DIR__, 2);
    }

    /**
     * Handle incoming Antigravity lifecycle hook
     */
    public function handle(string $hookType): void
    {
        // Read input JSON from stdin if available
        $stdin = file_get_contents('php://stdin');
        $payload = ! empty($stdin) ? json_decode($stdin, true) : [];

        switch ($hookType) {
            case 'stop':
                $this->handleStopHook($payload);
                break;

            case 'post-tool':
                $this->handlePostToolHook($payload);
                break;

            default:
                echo json_encode((object) []);
                break;
        }
    }

    /**
     * Handle Stop event
     */
    protected function handleStopHook(array $payload): void
    {
        // Check if architectural files were modified in git working directory
        if (! $this->hasArchitecturalModifications()) {
            echo json_encode((object) []);

            return;
        }

        // Check if there is drift
        $drift = $this->synchronizer->checkDrift();

        if (empty($drift)) {
            echo json_encode((object) []);

            return;
        }

        // Auto-synchronize
        $updated = $this->synchronizer->sync();

        // Check if new uncataloged plugin was introduced
        $newPluginDetected = false;
        $content = file_get_contents($this->synchronizer->getDocPath());
        if (str_contains($content, 'Local plugin package.')) {
            $newPluginDetected = true;
        }

        if ($newPluginDetected) {
            echo json_encode([
                'decision' => 'continue',
                'reason'   => 'A new plugin package was detected in plugins/webkul/. ARCHITECTURE.md metrics have been updated, but please complete its business domain and architectural role in Section 4.1.',
            ]);

            return;
        }

        // If updated cleanly, output empty object (or allow normal termination)
        echo json_encode((object) []);
    }

    /**
     * Handle PostToolUse event
     */
    protected function handlePostToolHook(array $payload): void
    {
        // In PostToolUse, always return empty object
        echo json_encode((object) []);
    }

    /**
     * Check git status for modified files in the architectural watchlist
     */
    public function hasArchitecturalModifications(): bool
    {
        $output = [];
        $returnCode = 0;
        @exec('git status --porcelain', $output, $returnCode);

        if ($returnCode !== 0 || empty($output)) {
            return false;
        }

        $watchlistPatterns = [
            'composer.json',
            'composer.lock',
            'database/migrations/',
            'plugins/webkul/',
            'bootstrap/providers.php',
            'NavigationGroup.php',
            'ARCHITECTURE.md',
        ];

        foreach ($output as $line) {
            $statusFile = trim(substr($line, 3));
            foreach ($watchlistPatterns as $pattern) {
                if (str_contains($statusFile, $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }
}
