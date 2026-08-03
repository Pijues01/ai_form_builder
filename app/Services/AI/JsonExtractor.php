<?php

namespace App\Services\AI;

class JsonExtractor
{
    /**
     * Pull the outermost JSON object out of a model reply, tolerating markdown
     * fences and surrounding prose. Returns null when no valid object is found.
     */
    public static function extract(string $text): ?array
    {
        $text = trim($text);

        $text = preg_replace('/```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```/', '', $text);

        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $end = self::balancedEnd($text, $start);
        if ($end === null) {
            return null;
        }

        $candidate = substr($text, $start, $end - $start + 1);

        $decoded = json_decode($candidate, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $repaired = self::repair($candidate);
        $decoded = $repaired === null ? null : json_decode($repaired, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected static function balancedEnd(string $text, int $start): ?int
    {
        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($char === '\\') {
                    $escape = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"') {
                $inString = true;
            } elseif ($char === '{' || $char === '[') {
                $depth++;
            } elseif ($char === '}' || $char === ']') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * Best-effort fixes for common truncation/trailing-comma issues.
     */
    protected static function repair(string $json): ?string
    {
        $json = preg_replace('/,\s*}/', '}', $json);
        $json = preg_replace('/,\s*]/', ']', $json);

        $json = preg_replace('/\{\s*([^{}\[\],"\':\s][^{}\[\],"\':]*?)\s*:/', '{"$1":', $json);

        return $json;
    }
}
