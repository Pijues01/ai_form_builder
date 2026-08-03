<?php

namespace App\Services\AI;

use App\Services\Schema\FieldTypeRegistry;

class MockFormGenerator
{
    /**
     * Deterministic offline generator used when AI_DRIVER=mock (default).
     * Maps prompt keywords to field types so the demo works without an API key.
     *
     * @return array{schema: array, model: string, tokens: int, latency_ms: int, attempts: int}
     */
    public function generate(string $prompt, ?array $existingSchema = null): array
    {
        if ($existingSchema !== null) {
            return [
                'schema' => $this->modify($existingSchema, $prompt),
                'model' => 'mock-v1',
                'tokens' => 0,
                'latency_ms' => 5,
                'attempts' => 1,
            ];
        }

        $prompt = trim($prompt);
        $detected = $this->detect($prompt);

        if (empty($detected)) {
            $detected = [
                ['type' => 'text', 'label' => 'Your Name', 'required' => true],
                ['type' => 'email', 'label' => 'Email Address', 'required' => true],
                ['type' => 'textarea', 'label' => 'Anything else we should know?', 'required' => false],
            ];
        }

        $title = $this->titleFrom($prompt);

        $sections = $this->bucket($detected);

        return [
            'schema' => [
                'title' => $title,
                'description' => 'Form generated from your request.',
                'sections' => $sections,
            ],
            'model' => 'mock-v1',
            'tokens' => 0,
            'latency_ms' => 5,
            'attempts' => 1,
        ];
    }

    protected function detect(string $prompt): array
    {
        $prompt = strtolower($prompt);
        $fields = [];
        $push = function (string $type, string $label, array $extra = []) use (&$fields, $prompt) {
            $required = str_contains($prompt, 'required') || str_contains($prompt, 'mandatory');
            $fields[] = array_merge([
                'type' => $type,
                'label' => $label,
                'required' => $required,
            ], $extra);
        };

        if (str_contains($prompt, 'name') || str_contains($prompt, 'candidate') || str_contains($prompt, 'applicant')) {
            $push('text', 'Full Name');
        }

        if (str_contains($prompt, 'email')) {
            $push('email', 'Email Address');
        }

        if (str_contains($prompt, 'phone') || str_contains($prompt, 'mobile') || str_contains($prompt, 'telephone')) {
            $push('phone', 'Phone Number');
        }

        if (str_contains($prompt, 'birth') || str_contains($prompt, 'dob') || (str_contains($prompt, 'date of') && str_contains($prompt, 'birth'))) {
            $push('date', 'Date of Birth');
        }

        if (str_contains($prompt, 'date') && ! str_contains($prompt, 'birth')) {
            $push('date', 'Date');
        }

        if (str_contains($prompt, 'time') && ! str_contains($prompt, 'full-time')) {
            $push('time', 'Preferred Time');
        }

        if (str_contains($prompt, 'address') || str_contains($prompt, 'location')) {
            $push('textarea', 'Address');
        }

        foreach (['message', 'comment', 'feedback', 'reason', 'note', 'details', 'describe', 'about'] as $kw) {
            if (str_contains($prompt, $kw)) {
                $push('textarea', ucfirst($kw).' Details');
                break;
            }
        }

        if (str_contains($prompt, 'education') || str_contains($prompt, 'degree') || str_contains($prompt, 'school') || str_contains($prompt, 'college')) {
            $push('textarea', 'Education History', ['help_text' => 'List degrees, institutions and years']);
        }

        if (str_contains($prompt, 'work') || str_contains($prompt, 'job') || str_contains($prompt, 'professional') || str_contains($prompt, 'career')) {
            $push('textarea', 'Work Experience', ['help_text' => 'Most recent role first']);
        }

        if (str_contains($prompt, 'skill') || str_contains($prompt, 'technolog') || str_contains($prompt, 'tools')) {
            $push('checkbox', 'Skills', ['options' => ['PHP', 'JavaScript', 'Python', 'UI/UX Design', 'Data Analysis']]);
        }

        if (str_contains($prompt, 'reference') || str_contains($prompt, 'referee')) {
            $push('text', 'Reference Contact', ['placeholder' => 'Name and contact details']);
        }

        if (str_contains($prompt, 'availability') || str_contains($prompt, 'start date') || str_contains($prompt, 'notice period')) {
            $push('dropdown', 'Availability', ['options' => ['Immediately', '2 weeks', '1 month', 'Custom']]);
        }

        if (str_contains($prompt, 'portfolio') || str_contains($prompt, 'sample')) {
            $push('url', 'Portfolio / Samples');
        }

        if (str_contains($prompt, 'location') || str_contains($prompt, 'relocat')) {
            $push('radio', 'Work Location', ['options' => ['On-site', 'Remote', 'Hybrid']]);
        }

        if (str_contains($prompt, 'url') || str_contains($prompt, 'website') || str_contains($prompt, 'linkedin') || str_contains($prompt, 'github')) {
            $push('url', 'Website / URL');
        }

        if (str_contains($prompt, 'age')) {
            $push('number', 'Age', ['validation' => ['min' => 1, 'max' => 100]]);
        }

        foreach (['gender', 'country', 'city', 'department', 'position', 'role', 'level', 'priority', 'status', 'experience'] as $kw) {
            if (str_contains($prompt, $kw)) {
                $push('dropdown', ucfirst($kw), ['options' => $this->optionsFor($kw)]);
                break;
            }
        }

        if (str_contains($prompt, 'full-time') || str_contains($prompt, 'part-time') || str_contains($prompt, 'contract')) {
            $push('dropdown', 'Employment Type', ['options' => ['Full-time', 'Part-time', 'Contract']]);
        }

        if (str_contains($prompt, 'rating') || str_contains($prompt, 'satisfaction') || str_contains($prompt, 'star')) {
            $push('rating', 'Rate your experience');
        }

        if (str_contains($prompt, 'file') || str_contains($prompt, 'upload') || str_contains($prompt, 'resume') || str_contains($prompt, 'cv') || str_contains($prompt, 'document') || str_contains($prompt, 'photo')) {
            $push('file', 'Attach a file', ['validation' => ['mimes' => ['pdf', 'doc', 'docx'], 'max_size' => 5120]]);
        }

        if (str_contains($prompt, 'agree') || str_contains($prompt, 'subscribe') || str_contains($prompt, 'newsletter') || str_contains($prompt, 'terms') || str_contains($prompt, 'interest')) {
            $push('checkbox', 'I agree', ['options' => ['I agree to the terms']]);
        }

        return $fields;
    }

    protected function optionsFor(string $keyword): array
    {
        return match ($keyword) {
            'gender' => ['Male', 'Female', 'Other', 'Prefer not to say'],
            'country' => ['India', 'United States', 'United Kingdom', 'Other'],
            'city' => ['New Delhi', 'Mumbai', 'Bengaluru', 'Other'],
            'department' => ['Engineering', 'Design', 'Marketing', 'Sales', 'HR'],
            'position', 'role' => ['Manager', 'Developer', 'Analyst', 'Intern', 'Other'],
            'level' => ['Beginner', 'Intermediate', 'Advanced'],
            'priority' => ['Low', 'Medium', 'High', 'Urgent'],
            'status' => ['Single', 'Married', 'Other'],
            'experience' => ['0-1 years', '1-3 years', '3-5 years', '5+ years'],
            default => ['Option A', 'Option B', 'Option C'],
        };
    }

    protected function titleFrom(string $prompt): string
    {
        $cleaned = preg_replace('/^(please\s+)?(create|generate|build|make|design)(\s+a|\s+an|\s+the)?(\s+new)?(\s+form)?(\s+for|\s+about|\s+regarding|\s+on)?\s*/i', '', $prompt);
        $cleaned = trim($cleaned);

        if ($cleaned === '' || strlen($cleaned) < 3) {
            return 'Generated Form';
        }

        $title = ucwords(substr($cleaned, 0, 48));

        return $title;
    }

    protected function bucket(array $detected): array
    {
        $personal = array_filter($detected, fn ($f) => in_array($f['type'], ['text', 'email', 'phone', 'date', 'time'], true));
        $details = array_filter($detected, fn ($f) => in_array($f['type'], ['textarea', 'number', 'url', 'dropdown', 'rating'], true));
        $final = array_filter($detected, fn ($f) => in_array($f['type'], ['file', 'checkbox'], true));

        $sections = [];
        if ($personal) {
            $sections[] = ['title' => 'Contact Details', 'fields' => array_values($personal)];
        }
        if ($details) {
            $sections[] = ['title' => 'Additional Information', 'fields' => array_values($details)];
        }
        if ($final) {
            $sections[] = ['title' => 'Final Steps', 'fields' => array_values($final)];
        }

        return $sections;
    }

    protected function modify(array $schema, string $prompt): array
    {
        $schema['description'] = 'Updated: '.trim($prompt);

        $sections = $schema['sections'] ?? [];
        if (! empty($sections) && isset($sections[0]['fields']) && is_array($sections[0]['fields'])) {
            $last = end($sections[0]['fields']);
            if (is_array($last)) {
                $sections[0]['fields'][] = FieldTypeRegistry::newField('text', 'Additional Notes');
            }
        }

        $schema['sections'] = $sections;

        return $schema;
    }
}
