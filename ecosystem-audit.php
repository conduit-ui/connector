#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Conduit-UI Ecosystem Package Audit Tool
 *
 * Validates that packages follow ecosystem consistency standards.
 * Run: php ecosystem-audit.php
 */
class EcosystemAudit
{
    private string $packageRoot;

    private array $findings = [];

    private array $scores = [];

    public function __construct(string $packageRoot = '.')
    {
        $this->packageRoot = rtrim($packageRoot, '/');
    }

    public function run(): int
    {
        echo "🔍 Conduit-UI Ecosystem Audit\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $this->auditFileStructure();
        $this->auditReadme();
        $this->auditComposerJson();
        $this->auditGitHubWorkflows();
        $this->auditBadges();

        $this->printReport();

        return $this->hasFailures() ? 1 : 0;
    }

    private function auditFileStructure(): void
    {
        echo "📁 File Structure\n";
        echo "─────────────────\n";

        $requiredFiles = [
            'README.md' => 'Project documentation',
            'composer.json' => 'Package manifest',
            'LICENSE' => 'MIT license file',
            'CHANGELOG.md' => 'Version history',
            'CONTRIBUTING.md' => 'Contribution guidelines',
            '.editorconfig' => 'Editor configuration',
        ];

        foreach ($requiredFiles as $file => $description) {
            $exists = file_exists("{$this->packageRoot}/{$file}");
            $this->record('file_structure', $file, $exists, $description);
        }

        $requiredDirs = [
            'src' => 'Source code',
            'tests' => 'Test suite',
            '.github/workflows' => 'CI/CD workflows',
        ];

        foreach ($requiredDirs as $dir => $description) {
            $exists = is_dir("{$this->packageRoot}/{$dir}");
            $this->record('file_structure', $dir, $exists, $description);
        }

        echo "\n";
    }

    private function auditReadme(): void
    {
        echo "📖 README.md\n";
        echo "─────────────\n";

        $readmePath = "{$this->packageRoot}/README.md";
        if (! file_exists($readmePath)) {
            $this->record('readme', 'file_exists', false, 'README.md must exist');
            echo "\n";

            return;
        }

        $content = file_get_contents($readmePath);

        $requiredSections = [
            'Installation' => '/##\s+Installation/i',
            'Quick Start' => '/##\s+Quick\s+Start/i',
            'Related Packages' => '/##\s+Related\s+Packages/i',
            'Testing' => '/##\s+Testing/i',
            'License' => '/##\s+License/i',
        ];

        foreach ($requiredSections as $section => $pattern) {
            $exists = preg_match($pattern, $content) === 1;
            $this->record('readme', "section_{$section}", $exists, "README must include {$section} section");
        }

        // Check for marketing-friendly language
        $hasVerbs = preg_match('/\b(enable|build|create|automate|manage|simplify)\b/i', $content) === 1;
        $this->record('readme', 'marketing_friendly', $hasVerbs, 'README should use action verbs (enable, build, etc.)');

        // Check for ecosystem branding
        $hasEcosystem = str_contains($content, 'conduit-ui') && str_contains($content, 'ecosystem');
        $this->record('readme', 'ecosystem_branding', $hasEcosystem, 'README should mention conduit-ui ecosystem');

        // Check for agent-friendly language
        $hasAgentFocus = str_contains(strtolower($content), 'agent');
        $this->record('readme', 'agent_focus', $hasAgentFocus, 'README should mention agent-friendly design');

        echo "\n";
    }

    private function auditComposerJson(): void
    {
        echo "📦 composer.json\n";
        echo "────────────────\n";

        $composerPath = "{$this->packageRoot}/composer.json";
        if (! file_exists($composerPath)) {
            $this->record('composer', 'file_exists', false, 'composer.json must exist');
            echo "\n";

            return;
        }

        $composer = json_decode(file_get_contents($composerPath), true);

        // Required fields
        $requiredFields = [
            'name' => 'Package name must be set',
            'description' => 'Package description must be set',
            'keywords' => 'Keywords must be set',
            'license' => 'License must be set (MIT)',
            'authors' => 'Authors must be listed',
        ];

        foreach ($requiredFields as $field => $message) {
            $exists = isset($composer[$field]) && ! empty($composer[$field]);
            $this->record('composer', "field_{$field}", $exists, $message);
        }

        // Check keywords
        if (isset($composer['keywords'])) {
            $hasConduitUi = in_array('conduit-ui', $composer['keywords'], true);
            $hasGitHub = in_array('github', $composer['keywords'], true);
            $hasAgents = in_array('agents', $composer['keywords'], true);
            $hasAutomation = in_array('automation', $composer['keywords'], true);

            $this->record('composer', 'keyword_conduit_ui', $hasConduitUi, 'Keywords should include "conduit-ui"');
            $this->record('composer', 'keyword_github', $hasGitHub, 'Keywords should include "github"');
            $this->record('composer', 'keyword_agents', $hasAgents, 'Keywords should include "agents"');
            $this->record('composer', 'keyword_automation', $hasAutomation, 'Keywords should include "automation"');
        }

        // Check scripts
        $requiredScripts = [
            'test' => 'Should have test script',
            'analyse' => 'Should have analyse script',
            'format' => 'Should have format script',
        ];

        foreach ($requiredScripts as $script => $message) {
            $exists = isset($composer['scripts'][$script]);
            $this->record('composer', "script_{$script}", $exists, $message);
        }

        // Check PHP version
        if (isset($composer['require']['php'])) {
            $phpVersion = $composer['require']['php'];
            $has82 = str_contains($phpVersion, '8.2');
            $this->record('composer', 'php_version', $has82, 'Should require PHP 8.2+');
        }

        echo "\n";
    }

    private function auditGitHubWorkflows(): void
    {
        echo "⚙️  GitHub Workflows\n";
        echo "──────────────────\n";

        $workflowsDir = "{$this->packageRoot}/.github/workflows";
        if (! is_dir($workflowsDir)) {
            $this->record('workflows', 'dir_exists', false, '.github/workflows directory must exist');
            echo "\n";

            return;
        }

        $requiredWorkflows = [
            'tests.yml' => 'Should have tests workflow',
            'gate.yml' => 'Should have Sentinel gate workflow',
        ];

        foreach ($requiredWorkflows as $workflow => $message) {
            $exists = file_exists("{$workflowsDir}/{$workflow}");
            $this->record('workflows', $workflow, $exists, $message);
        }

        echo "\n";
    }

    private function auditBadges(): void
    {
        echo "🏷️  Badges\n";
        echo "─────────\n";

        $readmePath = "{$this->packageRoot}/README.md";
        if (! file_exists($readmePath)) {
            echo "\n";

            return;
        }

        $content = file_get_contents($readmePath);

        $requiredBadges = [
            'version' => '/shields\.io\/packagist\/v\/conduit-ui/i',
            'downloads' => '/shields\.io\/packagist\/dt\/conduit-ui/i',
            'php_version' => '/shields\.io\/packagist\/php-v\/conduit-ui/i',
            'tests' => '/github\.com\/conduit-ui\/.*\/actions\/workflows\/(tests|gate)\.yml/i',
            'license' => '/shields\.io\/.*license/i',
        ];

        foreach ($requiredBadges as $badge => $pattern) {
            $exists = preg_match($pattern, $content) === 1;
            $this->record('badges', $badge, $exists, ucfirst(str_replace('_', ' ', $badge)).' badge should be present');
        }

        echo "\n";
    }

    private function record(string $category, string $key, bool $passed, string $message): void
    {
        $status = $passed ? '✅' : '❌';
        echo "{$status} {$message}\n";

        $this->findings[] = [
            'category' => $category,
            'key' => $key,
            'passed' => $passed,
            'message' => $message,
        ];

        if (! isset($this->scores[$category])) {
            $this->scores[$category] = ['passed' => 0, 'total' => 0];
        }

        $this->scores[$category]['total']++;
        if ($passed) {
            $this->scores[$category]['passed']++;
        }
    }

    private function printReport(): void
    {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📊 Summary\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $totalPassed = 0;
        $totalChecks = 0;

        foreach ($this->scores as $category => $score) {
            $percentage = $score['total'] > 0
                ? round(($score['passed'] / $score['total']) * 100)
                : 0;

            $status = $percentage === 100 ? '✅' : ($percentage >= 80 ? '⚠️' : '❌');

            echo sprintf(
                "%s %-20s %d/%d (%d%%)\n",
                $status,
                ucfirst(str_replace('_', ' ', $category)).':',
                $score['passed'],
                $score['total'],
                $percentage
            );

            $totalPassed += $score['passed'];
            $totalChecks += $score['total'];
        }

        $overallPercentage = $totalChecks > 0
            ? round(($totalPassed / $totalChecks) * 100)
            : 0;

        echo "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo sprintf(
            "Overall Score: %d/%d (%d%%)\n",
            $totalPassed,
            $totalChecks,
            $overallPercentage
        );
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        if ($overallPercentage === 100) {
            echo "\n🎉 Package meets all ecosystem standards!\n";
        } elseif ($overallPercentage >= 80) {
            echo "\n⚠️  Package mostly compliant, but needs improvements.\n";
        } else {
            echo "\n❌ Package needs significant work to meet ecosystem standards.\n";
        }

        echo "\n";
    }

    private function hasFailures(): bool
    {
        foreach ($this->findings as $finding) {
            if (! $finding['passed']) {
                return true;
            }
        }

        return false;
    }
}

// Run the audit
$audit = new EcosystemAudit(__DIR__);
exit($audit->run());
