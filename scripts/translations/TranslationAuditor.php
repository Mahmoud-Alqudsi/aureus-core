<?php

declare(strict_types=1);

namespace Scripts\Translations;

class TranslationAuditor
{
    protected string $pluginsRoot;

    protected ComponentScanner $componentScanner;

    protected LangFileAuditor $langAuditor;

    protected AutoFixer $autoFixer;

    public function __construct(?string $pluginsRoot = null)
    {
        $this->pluginsRoot = $pluginsRoot ?? realpath(__DIR__.'/../../plugins/webkul') ?: 'plugins/webkul';
        $this->componentScanner = new ComponentScanner;
        $this->langAuditor = new LangFileAuditor;
        $this->autoFixer = new AutoFixer;
    }

    /**
     * Get list of all plugin directory names.
     *
     * @return array<int, string>
     */
    public function getPluginsList(): array
    {
        $dirs = glob($this->pluginsRoot.'/*', GLOB_ONLYDIR);
        if (! $dirs) {
            return [];
        }

        $list = [];
        foreach ($dirs as $dir) {
            $list[] = basename($dir);
        }
        sort($list);

        return $list;
    }

    /**
     * Run full audit across specified plugins or all plugins.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function runAudit(array $options = []): array
    {
        $targetPlugins = isset($options['plugin']) && is_string($options['plugin'])
            ? explode(',', $options['plugin'])
            : $this->getPluginsList();

        $locales = isset($options['locales']) && is_string($options['locales'])
            ? explode(',', $options['locales'])
            : ['ar', 'es', 'fr', 'pt_BR'];

        $results = [
            'total_plugins' => count($targetPlugins),
            'summary'       => [
                'missing_labels'           => 0,
                'hardcoded_labels'         => 0,
                'hardcoded_sections'       => 0,
                'chained_duplicate_labels' => 0,
                'missing_lang_files'       => 0,
                'missing_lang_keys'        => 0,
                'untranslated_ar_values'   => 0,
                'duplicate_array_keys'     => 0,
            ],
            'plugins' => [],
        ];

        foreach ($targetPlugins as $pluginName) {
            $pluginPath = $this->pluginsRoot.'/'.$pluginName;
            if (! is_dir($pluginPath)) {
                continue;
            }

            $components = $this->componentScanner->scanPlugin($pluginPath);
            $lang = $this->langAuditor->auditPlugin($pluginPath, 'en', $locales);

            $pluginSummary = [
                'missing_labels'           => $components['stats']['missing_labels'],
                'hardcoded_labels'         => $components['stats']['hardcoded_labels'],
                'hardcoded_sections'       => $components['stats']['hardcoded_sections'],
                'chained_duplicate_labels' => $components['stats']['chained_duplicate_labels'],
                'missing_lang_files'       => $lang['stats']['missing_files'],
                'missing_lang_keys'        => $lang['stats']['missing_keys'],
                'untranslated_ar_values'   => $lang['stats']['untranslated_values'],
                'duplicate_array_keys'     => $lang['stats']['duplicate_array_keys'],
            ];

            $totalIssues = array_sum($pluginSummary);

            if ($totalIssues > 0 || ! empty($options['verbose'])) {
                $results['plugins'][$pluginName] = [
                    'summary'    => $pluginSummary,
                    'components' => $components,
                    'lang'       => $lang,
                ];
            }

            foreach ($pluginSummary as $metric => $count) {
                $results['summary'][$metric] += $count;
            }
        }

        return $results;
    }

    /**
     * Run automated fixes on target plugins.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function runFix(array $options = []): array
    {
        $targetPlugins = isset($options['plugin']) && is_string($options['plugin'])
            ? explode(',', $options['plugin'])
            : $this->getPluginsList();

        $fixedTotal = 0;
        $fixedFiles = [];

        foreach ($targetPlugins as $pluginName) {
            $pluginPath = $this->pluginsRoot.'/'.$pluginName;
            if (! is_dir($pluginPath)) {
                continue;
            }

            $scan = $this->componentScanner->scanPlugin($pluginPath);
            foreach ($scan['chained_duplicate_labels'] as $chained) {
                $file = realpath($chained['file']) ?: $chained['file'];
                if (! isset($fixedFiles[$file])) {
                    $res = $this->autoFixer->fixChainedDuplicateLabelsInFile($file);
                    if ($res['fixed'] > 0) {
                        $fixedTotal += $res['fixed'];
                        $fixedFiles[$file] = $res;
                    }
                }
            }
        }

        return [
            'total_fixed' => $fixedTotal,
            'files'       => $fixedFiles,
        ];
    }
}
