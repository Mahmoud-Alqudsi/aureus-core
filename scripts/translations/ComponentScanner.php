<?php

declare(strict_types=1);

namespace Scripts\Translations;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ComponentScanner
{
    /**
     * @var array<string, string>
     */
    protected array $components = [
        // Infolist entries
        'TextEntry'           => 'infolist_entry',
        'IconEntry'           => 'infolist_entry',
        'ImageEntry'          => 'infolist_entry',
        'ColorEntry'          => 'infolist_entry',
        'RepeatableEntry'     => 'infolist_entry',
        'InfolistTableColumn' => 'infolist_entry',

        // Table columns
        'TextColumn'     => 'table_column',
        'IconColumn'     => 'table_column',
        'ImageColumn'    => 'table_column',
        'ColorColumn'    => 'table_column',
        'ToggleColumn'   => 'table_column',
        'SelectColumn'   => 'table_column',
        'CheckboxColumn' => 'table_column',

        // Table filters
        'SelectFilter'  => 'table_filter',
        'Filter'        => 'table_filter',
        'TernaryFilter' => 'table_filter',

        // Grouping
        'Group' => 'table_group',
    ];

    /**
     * Scan a plugin's src directory for component label issues.
     *
     * @return array{
     *     missing_labels: array<int, array<string, mixed>>,
     *     hardcoded_labels: array<int, array<string, mixed>>,
     *     hardcoded_sections: array<int, array<string, mixed>>,
     *     chained_duplicate_labels: array<int, array<string, mixed>>,
     *     stats: array<string, int>
     * }
     */
    public function scanPlugin(string $pluginPath): array
    {
        $srcDir = rtrim($pluginPath, '/\\').'/src';
        if (! is_dir($srcDir)) {
            return [
                'missing_labels'           => [],
                'hardcoded_labels'         => [],
                'hardcoded_sections'       => [],
                'chained_duplicate_labels' => [],
                'stats'                    => [
                    'scanned_files'            => 0,
                    'missing_labels'           => 0,
                    'hardcoded_labels'         => 0,
                    'hardcoded_sections'       => 0,
                    'chained_duplicate_labels' => 0,
                ],
            ];
        }

        $missingLabels = [];
        $hardcodedLabels = [];
        $hardcodedSections = [];
        $chainedDuplicateLabels = [];
        $fileCount = 0;

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $fileCount++;
            $filePath = $file->getPathname();
            $content = (string) file_get_contents($filePath);
            $relPath = $this->getRelativePath($filePath);

            // 1. Chained duplicate ->label(...)
            $chained = $this->findChainedDuplicateLabels($content, $relPath);
            if (! empty($chained)) {
                $chainedDuplicateLabels = array_merge($chainedDuplicateLabels, $chained);
            }

            // 2. Sections, Tabs, and Fieldsets with hardcoded strings
            $sections = $this->findHardcodedSectionsAndTabs($content, $relPath);
            if (! empty($sections)) {
                $hardcodedSections = array_merge($hardcodedSections, $sections);
            }

            // 3. Components (Entries, Columns, Filters, Groups)
            $componentFindings = $this->findComponentIssues($content, $relPath);
            if (! empty($componentFindings['missing'])) {
                $missingLabels = array_merge($missingLabels, $componentFindings['missing']);
            }
            if (! empty($componentFindings['hardcoded'])) {
                $hardcodedLabels = array_merge($hardcodedLabels, $componentFindings['hardcoded']);
            }
        }

        return [
            'missing_labels'           => $missingLabels,
            'hardcoded_labels'         => $hardcodedLabels,
            'hardcoded_sections'       => $hardcodedSections,
            'chained_duplicate_labels' => $chainedDuplicateLabels,
            'stats'                    => [
                'scanned_files'            => $fileCount,
                'missing_labels'           => count($missingLabels),
                'hardcoded_labels'         => count($hardcodedLabels),
                'hardcoded_sections'       => count($hardcodedSections),
                'chained_duplicate_labels' => count($chainedDuplicateLabels),
            ],
        ];
    }

    /**
     * Find chained duplicate ->label(...) calls in file content.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function findChainedDuplicateLabels(string $content, string $relPath): array
    {
        $findings = [];
        $lines = explode("\n", $content);

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (preg_match('/->label\s*\((.*)\)/', $line, $match1)) {
                // Check if next non-empty line also starts with ->label(
                for ($j = $i + 1; $j < min($i + 4, count($lines)); $j++) {
                    $nextLine = trim($lines[$j]);
                    if ($nextLine === '') {
                        continue;
                    }
                    if (preg_match('/^->label\s*\((.*)\)/', $nextLine, $match2)) {
                        $findings[] = [
                            'file'         => $relPath,
                            'line'         => $i + 1,
                            'next_line'    => $j + 1,
                            'first_label'  => trim($match1[1]),
                            'second_label' => trim($match2[1]),
                            'snippet'      => trim($line).' '.trim($nextLine),
                        ];
                    }
                    break;
                }
            }
        }

        return $findings;
    }

    /**
     * Find Section::make('...'), Tab::make('...'), and Fieldset::make('...') with hardcoded strings.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function findHardcodedSectionsAndTabs(string $content, string $relPath): array
    {
        $findings = [];

        // Match Section::make('Title'), Tab::make('Title'), or Fieldset::make('Title')
        if (preg_match_all('/(?:Section|Tab|Fieldset)::make\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $m) {
                $title = trim($m[1][0]);
                $offset = $m[0][1];
                $line = substr_count(substr($content, 0, $offset), "\n") + 1;

                // If title is empty or placeholder like '-' or contains '::' (namespaced)
                if ($title === '' || preg_match('/^[\s\-—]+$/', $title) || strpos($title, '::') !== false) {
                    continue;
                }

                preg_match('/^(Section|Tab|Fieldset)/', $m[0][0], $elMatch);
                $element = $elMatch[1] ?? 'Structural';

                $findings[] = [
                    'file'    => $relPath,
                    'line'    => $line,
                    'element' => $element,
                    'title'   => $title,
                ];
            }
        }

        return $findings;
    }

    /**
     * Find missing labels or hardcoded string labels on components.
     *
     * @return array{missing: array<int, array<string, mixed>>, hardcoded: array<int, array<string, mixed>>}
     */
    protected function findComponentIssues(string $content, string $relPath): array
    {
        $missing = [];
        $hardcoded = [];

        foreach ($this->components as $component => $type) {
            // Match Component::make('fieldName') followed by fluent chain
            $pattern = '/('.$component.'::make\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\))((\s*->[a-zA-Z0-9_]+\s*\([^)]*\))*)/';
            if (! preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches as $m) {
                $name = $m[2][0];
                $chain = $m[3][0];
                $offset = $m[0][1];
                $line = substr_count(substr($content, 0, $offset), "\n") + 1;

                // If hiddenLabel() is called, intentional no label
                if (strpos($chain, 'hiddenLabel()') !== false) {
                    continue;
                }

                // Check for ->label(...)
                if (preg_match('/->label\s*\(([^)]+)\)/', $chain, $labelMatch)) {
                    $arg = trim($labelMatch[1]);

                    // Intentional blank label for image/icon table columns
                    if ($arg === "''" || $arg === '""' || $arg === 'false' || $arg === 'null') {
                        continue;
                    }

                    // Check if argument is translated
                    if (strpos($arg, '__(') === false && strpos($arg, 'trans(') === false) {
                        // Check if it's a closure or variable: fn() or $...
                        if (! str_starts_with($arg, 'fn') && ! str_starts_with($arg, '$')) {
                            $hardcoded[] = [
                                'file'      => $relPath,
                                'line'      => $line,
                                'component' => $component,
                                'type'      => $type,
                                'name'      => $name,
                                'label'     => trim($arg, "'\""),
                            ];
                        }
                    }
                } else {
                    $missing[] = [
                        'file'      => $relPath,
                        'line'      => $line,
                        'component' => $component,
                        'type'      => $type,
                        'name'      => $name,
                    ];
                }
            }
        }

        return [
            'missing'   => $missing,
            'hardcoded' => $hardcoded,
        ];
    }

    protected function getRelativePath(string $fullPath): string
    {
        $normalized = str_replace('\\', '/', $fullPath);
        $projectRoot = str_replace('\\', '/', realpath(__DIR__.'/../../') ?: '');

        if ($projectRoot !== '' && str_starts_with($normalized, $projectRoot)) {
            return ltrim(substr($normalized, strlen($projectRoot)), '/');
        }

        return $normalized;
    }
}
