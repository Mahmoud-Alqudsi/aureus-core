<?php

declare(strict_types=1);

namespace Scripts\Translations;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LangFileAuditor
{
    /**
     * @var array<int, string>
     */
    protected array $supportedLocales = ['ar', 'en', 'es', 'fr', 'pt_BR'];

    /**
     * Common acronyms/technical tokens to ignore when checking for untranslated strings.
     *
     * @var array<int, string>
     */
    protected array $ignoredTechnicalTokens = [
        'ID', 'SKU', 'URL', 'URI', 'SEO', 'UUID', 'ZIP', 'PDF', 'EAN', 'UPC', 'GTIN', 'ISBN',
        'HTML', 'CSS', 'JS', 'API', 'REST', 'RGB', 'HEX', 'POS', 'CRM', 'ERP', 'B2B', 'B2C',
        'VAT', 'UOM', 'Dymo', 'Webkul', 'Factur-X', 'XRechnung', 'NLCIUS', 'ASCII', 'IPv4', 'IPv6',
        'Codice Univoco Unità Organizzativa iPA', 'DIGSTORG', 'ERSTORG', 'Epson ePOS',
    ];

    /**
     * Audit language files for a given plugin path.
     *
     * @param  array<int, string>  $targetLocales
     * @return array{
     *     missing_files: array<int, array<string, mixed>>,
     *     missing_keys: array<int, array<string, mixed>>,
     *     untranslated_values: array<int, array<string, mixed>>,
     *     duplicate_array_keys: array<int, array<string, mixed>>,
     *     stats: array<string, int>
     * }
     */
    public function auditPlugin(string $pluginPath, string $sourceLocale = 'en', array $targetLocales = ['ar', 'es', 'fr', 'pt_BR']): array
    {
        $langDir = rtrim($pluginPath, '/\\').'/resources/lang';
        if (! is_dir($langDir)) {
            return [
                'missing_files'        => [],
                'missing_keys'         => [],
                'untranslated_values'  => [],
                'duplicate_array_keys' => [],
                'stats'                => [
                    'source_files'         => 0,
                    'missing_files'        => 0,
                    'missing_keys'         => 0,
                    'untranslated_values'  => 0,
                    'duplicate_array_keys' => 0,
                ],
            ];
        }

        $sourceDir = $langDir.'/'.$sourceLocale;
        if (! is_dir($sourceDir)) {
            return [
                'missing_files'        => [],
                'missing_keys'         => [],
                'untranslated_values'  => [],
                'duplicate_array_keys' => [],
                'stats'                => [
                    'source_files'         => 0,
                    'missing_files'        => 0,
                    'missing_keys'         => 0,
                    'untranslated_values'  => 0,
                    'duplicate_array_keys' => 0,
                ],
            ];
        }

        $missingFiles = [];
        $missingKeys = [];
        $untranslatedValues = [];
        $duplicateKeys = [];
        $sourceFileCount = 0;

        // 1. Check duplicate array keys across all locales' files
        $allLangFiles = $this->getAllPhpFiles($langDir);
        foreach ($allLangFiles as $filePath) {
            $dups = $this->findDuplicateArrayKeysInFile($filePath);
            if (! empty($dups)) {
                $duplicateKeys = array_merge($duplicateKeys, $dups);
            }
        }

        // 2. Iterate source files and compare against target locales
        $sourceFiles = $this->getAllPhpFiles($sourceDir);
        $sourceFileCount = count($sourceFiles);

        foreach ($sourceFiles as $sourceFile) {
            $relPath = substr($sourceFile, strlen($sourceDir) + 1);
            $sourceData = $this->loadArrayFile($sourceFile);

            if (! is_array($sourceData)) {
                continue;
            }

            $flattenedSource = $this->flattenKeys($sourceData);

            foreach ($targetLocales as $targetLocale) {
                if ($targetLocale === $sourceLocale) {
                    continue;
                }

                $targetFile = $langDir.'/'.$targetLocale.'/'.$relPath;
                if (! file_exists($targetFile)) {
                    $missingFiles[] = [
                        'source_file'   => $this->getRelativePath($sourceFile),
                        'target_locale' => $targetLocale,
                        'relative_file' => $relPath,
                    ];

                    continue;
                }

                $targetData = $this->loadArrayFile($targetFile);
                if (! is_array($targetData)) {
                    $missingFiles[] = [
                        'source_file'   => $this->getRelativePath($sourceFile),
                        'target_locale' => $targetLocale,
                        'relative_file' => $relPath,
                        'error'         => 'Failed to load array data',
                    ];

                    continue;
                }

                $flattenedTarget = $this->flattenKeys($targetData);

                // Check missing keys
                $missingInTarget = array_diff_key($flattenedSource, $flattenedTarget);
                if (! empty($missingInTarget)) {
                    foreach ($missingInTarget as $k => $sampleVal) {
                        $missingKeys[] = [
                            'target_locale' => $targetLocale,
                            'target_file'   => $this->getRelativePath($targetFile),
                            'key'           => $k,
                            'source_value'  => is_string($sampleVal) ? $sampleVal : json_encode($sampleVal),
                        ];
                    }
                }

                // Check untranslated values in Arabic
                if ($targetLocale === 'ar') {
                    $untranslated = $this->findUntranslatedValues($flattenedSource, $flattenedTarget, $targetFile);
                    if (! empty($untranslated)) {
                        $untranslatedValues = array_merge($untranslatedValues, $untranslated);
                    }
                }
            }
        }

        return [
            'missing_files'        => $missingFiles,
            'missing_keys'         => $missingKeys,
            'untranslated_values'  => $untranslatedValues,
            'duplicate_array_keys' => $duplicateKeys,
            'stats'                => [
                'source_files'         => $sourceFileCount,
                'missing_files'        => count($missingFiles),
                'missing_keys'         => count($missingKeys),
                'untranslated_values'  => count($untranslatedValues),
                'duplicate_array_keys' => count($duplicateKeys),
            ],
        ];
    }

    /**
     * Find duplicate array keys defined in the same PHP return array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findDuplicateArrayKeysInFile(string $filePath): array
    {
        $content = (string) file_get_contents($filePath);
        $tokens = token_get_all($content);
        $duplicates = [];
        $depth = 0;
        $currentKeysAtDepth = [];

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if ($token === '[' || (is_array($token) && $token[0] === T_ARRAY)) {
                $depth++;
                $currentKeysAtDepth[$depth] = [];
            } elseif ($token === ']' || $token === ')') {
                unset($currentKeysAtDepth[$depth]);
                $depth--;
            } elseif ($token === T_DOUBLE_ARROW && isset($currentKeysAtDepth[$depth])) {
                // Find key immediately preceding =>
                for ($j = $i - 1; $j >= 0; $j--) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
                        $keyName = trim($tokens[$j][1], "'\"");
                        if (in_array($keyName, $currentKeysAtDepth[$depth], true)) {
                            $duplicates[] = [
                                'file' => $this->getRelativePath($filePath),
                                'line' => $tokens[$j][2],
                                'key'  => $keyName,
                            ];
                        } else {
                            $currentKeysAtDepth[$depth][] = $keyName;
                        }
                        break;
                    }
                }
            }
        }

        return $duplicates;
    }

    /**
     * Find values in target language that remain identical to English phrases.
     *
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $target
     * @return array<int, array<string, mixed>>
     */
    protected function findUntranslatedValues(array $source, array $target, string $targetFile): array
    {
        $untranslated = [];

        foreach ($source as $k => $sourceVal) {
            if (! isset($target[$k])) {
                continue;
            }

            $targetVal = $target[$k];

            if (is_string($sourceVal) && is_string($targetVal)) {
                $trimmedSource = trim($sourceVal);
                $trimmedTarget = trim($targetVal);

                if ($trimmedSource === '' || $trimmedTarget === '') {
                    continue;
                }

                // If identical string and contains English word of at least 3 letters
                if ($trimmedSource === $trimmedTarget && preg_match('/[a-zA-Z]{3,}/', $trimmedTarget)) {
                    // Check if it's in the ignored tokens list
                    if (in_array($trimmedTarget, $this->ignoredTechnicalTokens, true)) {
                        continue;
                    }
                    if (preg_match('/^[A-Z0-9_\-\/]+$/', $trimmedTarget)) {
                        continue;
                    }

                    // Strip Laravel translation placeholders like :partner, :reason, :count
                    $withoutPlaceholders = trim((string) preg_replace('/:[a-zA-Z0-9_]+/', '', $trimmedTarget));
                    if (! preg_match('/[a-zA-Z]{3,}/', $withoutPlaceholders)) {
                        continue;
                    }

                    $untranslated[] = [
                        'file'  => $this->getRelativePath($targetFile),
                        'key'   => $k,
                        'value' => $trimmedTarget,
                    ];
                }
            }
        }

        return $untranslated;
    }

    /**
     * Flatten nested array into dot-notation keys.
     *
     * @param  array<string, mixed>  $array
     * @return array<string, mixed>
     */
    public function flattenKeys(array $array, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($array as $key => $value) {
            $currentKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flattenKeys($value, $currentKey));
            } else {
                $flattened[$currentKey] = $value;
            }
        }

        return $flattened;
    }

    /**
     * Safely load array from a PHP file.
     *
     * @return array<string, mixed>|null
     */
    protected function loadArrayFile(string $filePath): ?array
    {
        if (! file_exists($filePath)) {
            return null;
        }

        try {
            $data = include $filePath;

            return is_array($data) ? $data : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function getAllPhpFiles(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
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
