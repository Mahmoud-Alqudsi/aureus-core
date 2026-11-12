<?php

declare(strict_types=1);

namespace Scripts\Translations;

class AutoFixer
{
    /**
     * Fix chained duplicate labels in the given file.
     *
     * @return array{fixed: int, diff: array<int, string>}
     */
    public function fixChainedDuplicateLabelsInFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return ['fixed' => 0, 'diff' => []];
        }

        $content = (string) file_get_contents($filePath);
        $lines = explode("\n", $content);
        $fixedCount = 0;
        $diff = [];
        $linesToRemove = [];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // Match ->label(...) on current line
            if (preg_match('/^\s*->label\s*\((.*)\)\s*,?\s*$/', $line, $match1)) {
                // Find next non-empty line
                for ($j = $i + 1; $j < min($i + 4, count($lines)); $j++) {
                    $nextLine = $lines[$j];
                    if (trim($nextLine) === '') {
                        continue;
                    }

                    // If next non-empty line is also ->label(...)
                    if (preg_match('/^\s*->label\s*\((.*)\)\s*,?\s*$/', $nextLine, $match2)) {
                        $firstArg = trim($match1[1]);
                        $secondArg = trim($match2[1]);

                        // Prefer namespaced translation key if available
                        if (strpos($secondArg, '::') !== false || strpos($secondArg, '__(') !== false) {
                            $linesToRemove[] = $i;
                            $diff[] = '- Line '.($i + 1).': '.trim($line);
                            $diff[] = '+ Kept Line '.($j + 1).': '.trim($nextLine);
                            $fixedCount++;
                        } elseif (strpos($firstArg, '::') !== false) {
                            $linesToRemove[] = $j;
                            $diff[] = '- Line '.($j + 1).': '.trim($nextLine);
                            $diff[] = '+ Kept Line '.($i + 1).': '.trim($line);
                            $fixedCount++;
                        }
                    }
                    break;
                }
            }
        }

        if ($fixedCount > 0) {
            $newLines = [];
            foreach ($lines as $idx => $val) {
                if (! in_array($idx, $linesToRemove, true)) {
                    $newLines[] = $val;
                }
            }
            file_put_contents($filePath, implode("\n", $newLines));
        }

        return [
            'fixed' => $fixedCount,
            'diff'  => $diff,
        ];
    }
}
