<?php

namespace App\Services\Import;

use App\Services\Schema\FieldTypeRegistry;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use RuntimeException;

/**
 * Defensive .xlsx parser supporting two documented layouts:
 *
 *   Layout A - structured rows:
 *     Row 1: question | type | required | options | section
 *     Each data row is one field. "options" are separated by "|", "," or ";".
 *
 *   Layout B - plain header row:
 *     Row 1 is field labels (one column per field). An optional "type" column
 *     is honoured; otherwise the type is guessed. Sample data rows are ignored
 *     unless a column shows a small set of distinct values, in which case they
 *     are offered as radio options.
 */
class XlsxParser
{
    /**
     * @return array{title: string, description: string, sections: array, warnings: array<int, string>}
     */
    public function parse(string $path): array
    {
        $rows = $this->readRows($path);

        if (count($rows) < 1) {
            throw new RuntimeException('The .xlsx file has no rows.');
        }

        $layout = $this->detectLayout($rows[0]);
        $warnings = [];

        if ($layout === 'structured') {
            [$sections, $missing] = $this->parseStructured($rows);
        } else {
            [$sections, $missing] = $this->parsePlainHeader($rows);
            $warnings[] = 'Detected plain header-row layout (one field per column). Use the structured layout for explicit types/options.';
        }

        if ($sections === []) {
            throw new RuntimeException('No fields could be read from this sheet.');
        }

        foreach ($missing as $block) {
            $warnings[] = $block;
        }

        $title = $this->sheetTitle($path);

        return [
            'title' => $title,
            'description' => 'Imported from '.basename($path).' ('.$layout.' layout)',
            'sections' => $sections,
            'warnings' => $warnings,
        ];
    }

    private function readRows(string $path): array
    {
        try {
            $spreadsheet = IOFactory::load($path, IReader::READ_DATA_ONLY);
        } catch (\Throwable $e) {
            throw new RuntimeException('Could not read the .xlsx file: '.$e->getMessage());
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = array_map(fn ($row) => array_values(array_map(
            fn ($cell) => is_object($cell) && method_exists($cell, 'getRichText')
                ? $cell->getPlainText()
                : (is_scalar($cell) ? trim((string) $cell) : ''),
            $row
        )), $sheet->toArray(null, true, false, false));

        return array_values(array_filter($rows, fn ($row) => array_filter($row, fn ($cell) => $cell !== '')));
    }

    private function detectLayout(array $header): string
    {
        $norm = array_map(fn ($cell) => strtolower(trim((string) $cell)), $header);

        foreach ($norm as $cell) {
            if (preg_match('/question|label|field|prompt|column|option/', (string) $cell)) {
                return 'structured';
            }
        }

        return 'plain';
    }

    /**
     * @return array{array, array<int, string>}
     */
    private function parseStructured(array $rows): array
    {
        $header = array_map(fn ($cell) => strtolower((string) $cell), $rows[0]);

        $idx = function (array $needles) use ($header) {
            foreach ($needles as $needle) {
                foreach ($header as $i => $cell) {
                    if ($cell === $needle || str_contains($cell, $needle)) {
                        return $i;
                    }
                }
            }

            return null;
        };

        $q = $idx(['question', 'label', 'field', 'prompt']) ?? 0;
        $t = $idx(['type']);
        $r = $idx(['required']);
        $o = $idx(['options', 'choices', 'answers', 'choices']);
        $s = $idx(['section', 'category', 'group']);

        $guesser = new FieldTypeGuesser;
        $sections = [];
        $missing = [];

        $currentSection = null;

        foreach (array_slice($rows, 1) as $rowIndex => $row) {
            $question = trim((string) ($row[$q] ?? ''));

            if ($question === '') {
                $missing[] = 'Row '.($rowIndex + 2).' had no question text and was skipped.';

                continue;
            }

            if ($s !== null) {
                $sectionName = trim((string) ($row[$s] ?? ''));
                if ($sectionName !== '' && ($currentSection === null || $currentSection !== $sectionName)) {
                    $currentSection = $sectionName;
                    $sections[] = ['title' => $sectionName, 'fields' => []];
                }
            }

            if ($sections === []) {
                $sections[] = ['title' => 'Imported Questions', 'fields' => []];
            }

            $section = &$sections[count($sections) - 1];

            $explicitType = $t !== null ? $this->normalizeType((string) ($row[$t] ?? '')) : null;
            $options = $o !== null ? $this->splitOptions((string) ($row[$o] ?? '')) : [];
            $required = $r !== null && $this->truthy((string) ($row[$r] ?? ''));

            $guess = $guesser->guess($question, $options ?: FieldTypeGuesser::knownOptionsFor($question), $explicitType);

            $section['fields'][] = [
                'type' => $guess['type'],
                'label' => $question,
                'required' => $required || $guess['required'],
                'options' => $guess['options'],
                'confidence' => $explicitType !== null ? 'high' : $guess['confidence'],
                'help_text' => null,
            ];
        }

        return [$sections, $missing];
    }

    /**
     * @return array{array, array<int, string>}
     */
    private function parsePlainHeader(array $rows): array
    {
        $header = $rows[0];
        $data = array_slice($rows, 1);

        $typeIndex = null;
        foreach ($header as $i => $cell) {
            if (strtolower(trim((string) $cell)) === 'type') {
                $typeIndex = $i;
            }
        }

        $guesser = new FieldTypeGuesser;
        $sections = [['title' => 'Imported Questions', 'fields' => []]];

        foreach ($header as $i => $label) {
            $label = trim((string) $label);

            if ($label === '' || $i === $typeIndex) {
                continue;
            }

            $explicitType = $typeIndex !== null ? $this->normalizeType((string) ($data[0][$typeIndex] ?? '')) : null;

            $distinct = $this->distinctValues($data, $i);
            $options = ($distinct !== [] && $explicitType === null) ? $distinct : [];

            $guess = $guesser->guess($label, $options, $explicitType);

            $sections[0]['fields'][] = [
                'type' => $guess['type'],
                'label' => $label,
                'required' => $guess['required'],
                'options' => $guess['options'],
                'confidence' => $explicitType !== null ? 'high' : $guess['confidence'],
                'help_text' => null,
            ];
        }

        return [$sections, []];
    }

    private function normalizeType(string $type): ?string
    {
        $type = strtolower(trim($type));

        $aliases = [
            'text' => 'text', 'single line' => 'text', 'short text' => 'text',
            'textarea' => 'textarea', 'paragraph' => 'textarea', 'long text' => 'textarea', 'multiline' => 'textarea',
            'number' => 'number', 'numeric' => 'number', 'integer' => 'number',
            'email' => 'email', 'e-mail' => 'email',
            'phone' => 'phone', 'telephone' => 'phone',
            'url' => 'url', 'website' => 'url', 'link' => 'url',
            'date' => 'date', 'datetime' => 'date',
            'time' => 'time',
            'dropdown' => 'dropdown', 'select' => 'dropdown', 'choice' => 'dropdown',
            'radio' => 'radio', 'multiple choice' => 'radio',
            'checkbox' => 'checkbox', 'multi-select' => 'checkbox', 'multiselect' => 'checkbox',
            'file' => 'file', 'upload' => 'file', 'file upload' => 'file',
            'rating' => 'rating', 'stars' => 'rating', 'score' => 'rating',
        ];

        return $aliases[$type] ?? (FieldTypeRegistry::exists($type) ? $type : null);
    }

    /**
     * @return array<int, string>
     */
    private function splitOptions(string $value): array
    {
        $options = array_values(array_filter(array_map('trim', preg_split('/[|,;]/', $value) ?: []), fn ($o) => $o !== ''));

        return array_slice($options, 0, 12);
    }

    private function truthy(string $value): bool
    {
        return in_array(strtolower($value), ['yes', 'y', 'true', '1', 'required', 'mandatory'], true);
    }

    private function sheetTitle(string $path): string
    {
        $base = pathinfo($path, PATHINFO_FILENAME);

        return ucwords(str_replace(['-', '_'], ' ', $base));
    }

    /**
     * Distinct non-empty sample values for a column, only when the set is small
     * enough to plausibly be choices rather than free text.
     *
     * @return array<int, string>
     */
    private function distinctValues(array $data, int $column): array
    {
        $values = [];

        foreach ($data as $row) {
            $value = trim((string) ($row[$column] ?? ''));

            if ($value === '') {
                continue;
            }

            $values[$value] = true;
        }

        $values = array_keys($values);

        if (count($values) < 2 || count($values) > 4 || count($values) === count($data)) {
            return [];
        }

        return array_values($values);
    }
}
