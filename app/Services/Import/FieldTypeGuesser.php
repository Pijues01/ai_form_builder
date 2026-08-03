<?php

namespace App\Services\Import;

use App\Services\Schema\FieldTypeRegistry;

/**
 * Deterministic, defensive type inference for imported questions.
 *
 * The first pass is pure heuristics on the label text. Ambiguous rows can be
 * upgraded later with an optional AI pass (see FormImportService) - but the
 * heuristic result is always a safe, schema-valid default.
 */
class FieldTypeGuesser
{
    /**
     * @return array{type: string, required: bool, options: array<int, string>, confidence: string}
     */
    public function guess(string $label, array $options = [], ?string $explicitType = null): array
    {
        $text = mb_strtolower(trim($label));

        if ($explicitType !== null && FieldTypeRegistry::exists($explicitType)) {
            return [
                'type' => $explicitType,
                'required' => $this->looksRequired($text),
                'options' => $options,
                'confidence' => 'high',
            ];
        }

        $type = 'text';
        $confidence = 'low';

        if ($options !== []) {
            $type = 'radio';
            $confidence = 'high';
        }

        if (preg_match('/checkbox|check all|select all|agree|consent|subscribe|terms|interests/i', $text)) {
            $type = 'checkbox';
            $confidence = 'high';
        } elseif (preg_match('/select|choose|prefer(red)? (option|type|gender)|which|department|country|state|city|role|level|status|priority|category|grade|frequency/i', $text)) {
            $type = 'dropdown';
            $confidence = 'high';
        } elseif (preg_match('/e-?mail|email address/i', $text)) {
            $type = 'email';
            $confidence = 'high';
        } elseif (preg_match('/\b(?:phone|mobile|telephone)\b|phone number|mobile number|contact number|contact no\.?/i', $text)) {
            $type = 'phone';
            $confidence = 'high';
        } elseif (preg_match('/website|web ?site|url|linkedin|portfolio|github/i', $text)) {
            $type = 'url';
            $confidence = 'high';
        } elseif (preg_match('/date of birth|dob|birth ?date|start date|end date|date/i', $text)) {
            $type = 'date';
            $confidence = 'high';
        } elseif (preg_match('/\btime\b|preferred time|what time/i', $text)) {
            $type = 'time';
            $confidence = 'high';
        } elseif (preg_match('/rating|rate |score|stars?/i', $text)) {
            $type = 'rating';
            $confidence = 'high';
        } elseif (preg_match('/upload|attach|resume|cv|document|file|photo|image/i', $text)) {
            $type = 'file';
            $confidence = 'high';
        } elseif (preg_match('/age|how old|years? of|number of|quantity|count|budget|salary|amount|years?|percentage/i', $text)) {
            $type = 'number';
            $confidence = 'high';
        } elseif (preg_match('/describe|explain|why|tell us|comments?|feedback|notes|message|details|history|experience|qualifications/i', $text)) {
            $type = 'textarea';
            $confidence = 'high';
        } elseif (preg_match('/\?$/', $text)) {
            $type = 'text';
            $confidence = 'low';
        }

        return [
            'type' => $type,
            'required' => $this->looksRequired($text),
            'options' => $options,
            'confidence' => $confidence,
        ];
    }

    public function looksRequired(string $label): bool
    {
        return (bool) preg_match('/required|mandatory|\*$|\(required\)/i', $label);
    }

    /**
     * @return array<int, string>
     */
    public static function knownOptionsFor(string $label): array
    {
        $text = mb_strtolower(trim($label));

        $map = [
            'gender' => ['Male', 'Female', 'Other', 'Prefer not to say'],
            'country' => ['India', 'United States', 'United Kingdom', 'Other'],
            'state' => ['Karnataka', 'Maharashtra', 'Delhi', 'Other'],
            'marital status' => ['Single', 'Married', 'Divorced', 'Other'],
            'employment type' => ['Full-time', 'Part-time', 'Contract', 'Internship'],
            'department' => ['Engineering', 'Design', 'Marketing', 'Sales', 'HR'],
            'priority' => ['Low', 'Medium', 'High', 'Urgent'],
            'frequency' => ['Daily', 'Weekly', 'Monthly', 'Yearly'],
            'rating' => ['1', '2', '3', '4', '5'],
        ];

        foreach ($map as $keyword => $options) {
            if (str_contains($text, $keyword)) {
                return $options;
            }
        }

        return [];
    }
}
