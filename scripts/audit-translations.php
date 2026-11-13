#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__.'/translations/ComponentScanner.php';
require_once __DIR__.'/translations/LangFileAuditor.php';
require_once __DIR__.'/translations/AutoFixer.php';
require_once __DIR__.'/translations/TranslationAuditor.php';

use Scripts\Translations\TranslationAuditor;

$options = getopt('v', ['check', 'fix', 'json', 'plugin:', 'locales:', 'verbose', 'help']);

if (isset($options['help'])) {
    echo <<<'HELP'
Aureus ERP — Translation & Localization Auditor CLI

Usage:
  php scripts/audit-translations.php [options]

Options:
  --check            CI check mode. Exits 0 if clean, exits 1 if localization issues found.
  --fix              Automatically fix safe issues (e.g. chained duplicate ->label() calls).
  --plugin=<names>   Audit specific plugin(s), comma-separated (e.g. --plugin=sales,accounts).
  --locales=<list>   Target locales to compare against 'en' (default: ar,es,fr,pt_BR).
  --json             Output full structured audit results in JSON format.
  -v, --verbose      Display detailed findings per file.
  --help             Show this help message.

Examples:
  php scripts/audit-translations.php
  php scripts/audit-translations.php --check
  php scripts/audit-translations.php --plugin=sales -v
  php scripts/audit-translations.php --fix

HELP;
    exit(0);
}

$auditor = new TranslationAuditor;

// 1. Fix Mode
if (isset($options['fix'])) {
    echo 'Running automated localization fixes...'.PHP_EOL;
    $fixResult = $auditor->runFix($options);

    echo "Fixed {$fixResult['total_fixed']} chained duplicate label call(s) across ".count($fixResult['files']).' file(s).'.PHP_EOL;
    foreach ($fixResult['files'] as $filePath => $details) {
        echo "  [FIXED] {$filePath}: {$details['fixed']} occurrence(s)".PHP_EOL;
        if (isset($options['verbose']) || isset($options['v'])) {
            foreach ($details['diff'] as $d) {
                echo "          {$d}".PHP_EOL;
            }
        }
    }

    echo PHP_EOL.'Re-running audit on fixed plugins...'.PHP_EOL.PHP_EOL;
}

// 2. Run Audit
$verbose = isset($options['verbose']) || isset($options['v']);
$auditData = $auditor->runAudit(array_merge($options, ['verbose' => $verbose]));

// 3. JSON Output Mode
if (isset($options['json'])) {
    echo json_encode($auditData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    $totalIssues = array_sum($auditData['summary']);
    exit(isset($options['check']) && $totalIssues > 0 ? 1 : 0);
}

// 4. Console Human Output Mode
echo PHP_EOL.'================================================================================'.PHP_EOL;
echo '               AUREUS ERP — LOCALIZATION & TRANSLATION AUDIT'.PHP_EOL;
echo '================================================================================'.PHP_EOL.PHP_EOL;

$summary = $auditData['summary'];
$totalIssues = array_sum($summary);

echo sprintf("%-20s | %-10s | %-10s | %-9s | %-10s | %-8s\n", 'Plugin', 'Miss Label', 'Hardcoded', 'Chained', 'Lang Gaps', 'Total');
echo str_repeat('-', 80).PHP_EOL;

$pluginsWithIssues = 0;
foreach ($auditData['plugins'] as $pluginName => $pData) {
    $pSummary = $pData['summary'];
    $pTotal = array_sum($pSummary);
    if ($pTotal === 0) {
        continue;
    }

    $pluginsWithIssues++;
    $hardcodedTotal = $pSummary['hardcoded_labels'] + $pSummary['hardcoded_sections'];
    $langGapsTotal = $pSummary['missing_lang_files'] + $pSummary['missing_lang_keys'] + $pSummary['untranslated_ar_values'] + $pSummary['duplicate_array_keys'];

    echo sprintf(
        "%-20s | %-10d | %-10d | %-9d | %-10d | %-8d\n",
        $pluginName,
        $pSummary['missing_labels'],
        $hardcodedTotal,
        $pSummary['chained_duplicate_labels'],
        $langGapsTotal,
        $pTotal
    );

    if ($verbose) {
        if (! empty($pData['components']['chained_duplicate_labels'])) {
            echo "   [!] Chained duplicate labels:\n";
            foreach ($pData['components']['chained_duplicate_labels'] as $c) {
                echo "       - {$c['file']}:{$c['line']} => {$c['first_label']} THEN {$c['second_label']}\n";
            }
        }
        if (! empty($pData['components']['hardcoded_sections'])) {
            echo "   [!] Hardcoded sections/tabs:\n";
            foreach ($pData['components']['hardcoded_sections'] as $s) {
                echo "       - {$s['file']}:{$s['line']} => {$s['element']}('{$s['title']}')\n";
            }
        }
        if (! empty($pData['components']['hardcoded_labels'])) {
            echo "   [!] Hardcoded component labels:\n";
            foreach (array_slice($pData['components']['hardcoded_labels'], 0, 5) as $h) {
                echo "       - {$h['file']}:{$h['line']} => {$h['component']}('{$h['name']}')->label('{$h['label']}')\n";
            }
        }
        if (! empty($pData['lang']['untranslated_values'])) {
            echo "   [!] Untranslated English in Arabic:\n";
            foreach (array_slice($pData['lang']['untranslated_values'], 0, 5) as $u) {
                echo "       - {$u['file']}: key '{$u['key']}' => '{$u['value']}'\n";
            }
        }
    }
}

echo str_repeat('=', 80).PHP_EOL;
echo 'AUDIT SUMMARY:'.PHP_EOL;
echo "  - Total plugins scanned:           {$auditData['total_plugins']}".PHP_EOL;
echo "  - Plugins with localization gaps:  {$pluginsWithIssues}".PHP_EOL;
echo "  - Missing component labels:        {$summary['missing_labels']}".PHP_EOL;
echo "  - Hardcoded component labels:      {$summary['hardcoded_labels']}".PHP_EOL;
echo "  - Hardcoded sections/tabs:         {$summary['hardcoded_sections']}".PHP_EOL;
echo "  - Chained duplicate label calls:   {$summary['chained_duplicate_labels']}".PHP_EOL;
echo "  - Missing language files:          {$summary['missing_lang_files']}".PHP_EOL;
echo "  - Missing language keys:           {$summary['missing_lang_keys']}".PHP_EOL;
echo "  - Untranslated Arabic values:      {$summary['untranslated_ar_values']}".PHP_EOL;
echo "  - Duplicate array keys in lang:    {$summary['duplicate_array_keys']}".PHP_EOL;
echo '  -------------------------------------------------------------'.PHP_EOL;
echo "  - TOTAL ISSUES DETECTED:           {$totalIssues}".PHP_EOL.PHP_EOL;

if ($totalIssues === 0) {
    echo ' [PASS] All scanned plugins comply with localization and translation standards.'.PHP_EOL;
    exit(0);
} else {
    if (isset($options['check'])) {
        fwrite(STDERR, " [FAIL] Localization gaps or label duplications detected ({$totalIssues} issues).".PHP_EOL);
        fwrite(STDERR, "        Run 'php scripts/audit-translations.php --fix' to auto-fix duplicate chained labels,".PHP_EOL);
        fwrite(STDERR, '        and review reported missing labels and hardcoded strings.'.PHP_EOL);
        exit(1);
    } else {
        echo " [WARN] Run with '--fix' to auto-resolve chained duplicates, or pass '-v' for detailed logs.".PHP_EOL;
        exit(0);
    }
}
