<?php

namespace App\Services\Import;

use DOMDocument;
use DOMXPath;
use RuntimeException;

/**
 * Defensive .docx parser.
 *
 * Reads the raw OOXML (word/document.xml) instead of going through a reader
 * library so heading styles, list numbering and checkbox glyphs are detected
 * exactly as Word wrote them. Anything we cannot map (tables, empty blocks)
 * is reported in $warnings rather than silently dropped.
 */
class DocxParser
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const CHECKBOX_GLYPHS = ['☐', '☑', '☒'];

    /**
     * @return array{title: string, description: string, sections: array, warnings: array<int, string>}
     */
    public function parse(string $path): array
    {
        $xml = $this->readDocumentXml($path);

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        if (! $dom->loadXML($xml)) {
            throw new RuntimeException('Not a valid Word document (document.xml is malformed).');
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W);

        $warnings = [];

        if ($xpath->query('//w:body/w:tbl')->length > 0) {
            $warnings[] = 'Skipped '.$xpath->query('//w:body/w:tbl')->length.' table(s): tables are not imported, only headings, questions and lists.';
        }

        $draft = [
            'title' => '',
            'description' => '',
            'sections' => [],
        ];

        $guesser = new FieldTypeGuesser;

        foreach ($xpath->query('//w:body/w:p') as $node) {
            $style = strtolower((string) $xpath->evaluate('string(w:pPr/w:pStyle/@w:val)', $node));
            $isList = $xpath->query('w:pPr/w:numPr', $node)->length > 0;
            $text = $this->cleanText((string) $xpath->evaluate('string(.)', $node));

            if ($text === '') {
                continue;
            }

            if ($style === 'title') {
                if ($draft['title'] === '') {
                    $draft['title'] = $text;
                }

                continue;
            }

            if ($style === 'subtitle') {
                $draft['description'] = trim($draft['description']."\n".$text);

                continue;
            }

            if (str_starts_with($style, 'heading')) {
                // The first Heading1 acts as the document title when no Title
                // style was used; everything else becomes a section.
                if ($style === 'heading1' && $draft['title'] === '' && $draft['sections'] === []) {
                    $draft['title'] = $text;
                    $draft['sections'][] = [
                        'title' => $text,
                        'fields' => [],
                    ];

                    continue;
                }

                $draft['sections'][] = [
                    'title' => $text,
                    'fields' => [],
                ];

                continue;
            }

            // A line written in Title Case with no sentence punctuation can be a
            // heading even if the file did not use a Heading style. Questions and
            // sentences are never treated as headings.
            if (! $isList && $this->looksLikeHeading($text)) {
                $draft['sections'][] = [
                    'title' => $text,
                    'fields' => [],
                ];

                continue;
            }

            if ($isList || $this->hasCheckboxGlyph($text) || $this->looksLikeBullet($text)) {
                $this->attachOption($draft, $this->stripBulletPrefix($text), $this->hasCheckboxGlyph($text));

                continue;
            }

            $this->addField($draft, $text, $guesser);
        }

        if ($draft['sections'] === []) {
            $draft['sections'][] = ['title' => 'Imported Questions', 'fields' => []];
        }

        // Drop empty sections (headings with no questions under them).
        $draft['sections'] = array_values(array_filter($draft['sections'], fn ($s) => $s['fields'] !== []));

        if ($draft['sections'] === []) {
            throw new RuntimeException('No questions found in this document. Add questions as plain paragraphs and options as bullet lists.');
        }

        if ($draft['title'] === '') {
            $draft['title'] = basename($path);
        }

        return $draft + ['warnings' => $warnings];
    }

    private function readDocumentXml(string $path): string
    {
        $zip = new \ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open .docx (is it a valid Word file?).');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false || $xml === '') {
            throw new RuntimeException('The .docx contains no document.xml body.');
        }

        return $xml;
    }

    private function cleanText(string $text): string
    {
        $text = str_replace(["\t", "\r"], ' ', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return $text;
    }

    private function hasCheckboxGlyph(string $text): bool
    {
        foreach (self::CHECKBOX_GLYPHS as $glyph) {
            if (str_contains($text, $glyph)) {
                return true;
            }
        }

        return (bool) preg_match('/^\[\s?\]|^\[[xX]\]/', $text);
    }

    private function looksLikeBullet(string $text): bool
    {
        return (bool) preg_match('/^[-•*·]\s+/', $text);
    }

    private function stripBulletPrefix(string $text): string
    {
        foreach (self::CHECKBOX_GLYPHS as $glyph) {
            $text = str_replace($glyph, '', $text);
        }

        return trim(preg_replace('/^\[\s?\]|^\[[xX]\]/', '', $text) ?? '');
    }

    private function looksLikeHeading(string $text): bool
    {
        if (preg_match('/[?.!]$/', $text)) {
            return false;
        }

        $words = str_word_count($text);

        if ($words < 1 || $words > 6) {
            return false;
        }

        // Every word must start with an uppercase letter (Title Case) or the
        // whole line is ALL-CAPS. "Full name", "Email address" etc. fail here
        // and are correctly treated as questions.
        $isTitleCase = (bool) preg_match('/^(?:[A-Z0-9][^\s]*\s*)+$/', trim($text));

        $isAllCaps = (bool) preg_match('/^[A-Z0-9\s,]+$/', trim($text));

        return $isTitleCase || $isAllCaps;
    }

    /**
     * Attach a list item to the current field as an option, or start a
     * checkbox field when a checkbox list appears with no preceding question.
     */
    private function attachOption(array &$draft, string $text, bool $isCheckbox): void
    {
        $field = $this->lastField($draft);

        if ($field === null) {
            $section = &$draft['sections'][count($draft['sections']) - 1];
            $section['fields'][] = [
                'type' => 'checkbox',
                'label' => 'Select all that apply',
                'required' => false,
                'options' => [$text],
                'confidence' => 'low',
                'help_text' => null,
            ];

            return;
        }

        $field['options'][] = $text;
        $field['type'] = $isCheckbox ? 'checkbox' : ($field['type'] === 'text' ? 'radio' : $field['type']);
        $this->setLastField($draft, $field);
    }

    private function addField(array &$draft, string $text, FieldTypeGuesser $guesser): void
    {
        if ($draft['sections'] === []) {
            $draft['sections'][] = ['title' => 'Imported Questions', 'fields' => []];
        }

        $section = &$draft['sections'][count($draft['sections']) - 1];

        $guess = $guesser->guess($text, FieldTypeGuesser::knownOptionsFor($text));

        $section['fields'][] = [
            'type' => $guess['type'],
            'label' => $text,
            'required' => $guess['required'],
            'options' => $guess['options'],
            'confidence' => $guess['confidence'],
            'help_text' => null,
        ];
    }

    private function lastField(array &$draft): ?array
    {
        if ($draft['sections'] === []) {
            return null;
        }

        $section = $draft['sections'][count($draft['sections']) - 1];

        return empty($section['fields']) ? null : $section['fields'][count($section['fields']) - 1];
    }

    private function setLastField(array &$draft, array $field): void
    {
        $index = count($draft['sections']) - 1;
        $draft['sections'][$index]['fields'][count($draft['sections'][$index]['fields']) - 1] = $field;
    }
}
