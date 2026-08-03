<?php

namespace App\Services\Templates;

use App\Services\Schema\FieldTypeRegistry;

class FormTemplates
{
    /**
     * Built-in form templates used to bootstrap new forms quickly.
     */
    public static function all(): array
    {
        return [
            self::contactUs(),
            self::eventRegistration(),
            self::jobApplication(),
            self::feedback(),
        ];
    }

    public static function get(string $slug): ?array
    {
        foreach (self::all() as $template) {
            if ($template['slug'] === $slug) {
                return $template;
            }
        }

        return null;
    }

    protected static function contactUs(): array
    {
        return [
            'slug' => 'contact-us',
            'name' => 'Contact Us',
            'description' => 'A simple contact form with a topic selector.',
            'icon' => '✉️',
            'schema' => [
                'title' => 'Contact Us',
                'description' => 'Have a question or comment? Send us a message.',
                'sections' => [
                    [
                        'title' => 'Your details',
                        'fields' => [
                            self::field('text', 'Full name', 'full_name', ['required' => true]),
                            self::field('email', 'Email address', 'email', ['required' => true]),
                            self::field('dropdown', 'Topic', 'topic', [
                                'required' => true,
                                'options' => ['General question', 'Support', 'Billing', 'Press', 'Other'],
                            ]),
                        ],
                    ],
                    [
                        'title' => 'Message',
                        'fields' => [
                            self::field('textarea', 'Message', 'message', ['required' => true, 'help_text' => 'Please include as much detail as you can.']),
                            self::field('checkbox', 'Contact preferences', 'preferences', ['options' => ['Send me product updates']]),
                        ],
                    ],
                ],
            ],
        ];
    }

    protected static function eventRegistration(): array
    {
        return [
            'slug' => 'event-registration',
            'name' => 'Event Registration',
            'description' => 'Register attendees with attendance mode and dietary needs.',
            'icon' => '🎟️',
            'schema' => [
                'title' => 'Event Registration',
                'description' => 'Register for our upcoming community event.',
                'sections' => [
                    [
                        'title' => 'Attendee',
                        'fields' => [
                            self::field('text', 'Full name', 'full_name', ['required' => true]),
                            self::field('email', 'Email address', 'email', ['required' => true]),
                            self::field('phone', 'Phone number', 'phone'),
                        ],
                    ],
                    [
                        'title' => 'Attendance',
                        'fields' => [
                            self::field('radio', 'Attendance mode', 'attendance_mode', [
                                'required' => true,
                                'options' => ['In person', 'Virtual'],
                            ]),
                            self::field('dropdown', 'Dietary requirements', 'dietary', [
                                'options' => ['None', 'Vegetarian', 'Vegan', 'Gluten-free', 'Other'],
                            ]),
                            self::field('checkbox', 'Terms', 'agree_terms', [
                                'required' => true,
                                'options' => ['I agree to the code of conduct'],
                            ]),
                        ],
                    ],
                ],
            ],
        ];
    }

    protected static function jobApplication(): array
    {
        return [
            'slug' => 'job-application',
            'name' => 'Job Application',
            'description' => 'Collect applications with role, experience and a resume upload.',
            'icon' => '💼',
            'schema' => [
                'title' => 'Job Application',
                'description' => 'Apply for an open position at our company.',
                'sections' => [
                    [
                        'title' => 'About you',
                        'fields' => [
                            self::field('text', 'Full name', 'full_name', ['required' => true]),
                            self::field('email', 'Email address', 'email', ['required' => true]),
                            self::field('dropdown', 'Position', 'position', [
                                'required' => true,
                                'options' => ['Frontend Developer', 'Backend Developer', 'Product Designer', 'Marketing', 'Other'],
                            ]),
                        ],
                    ],
                    [
                        'title' => 'Experience',
                        'fields' => [
                            self::field('number', 'Years of experience', 'years_experience', ['required' => true, 'min' => 0, 'max' => 50]),
                            self::field('url', 'Portfolio or LinkedIn', 'portfolio'),
                            self::field('textarea', 'Cover letter', 'cover_letter', ['required' => true]),
                            self::field('file', 'Resume', 'resume', ['required' => true, 'mimes' => ['pdf', 'doc', 'docx'], 'max_size' => 5 * 1024 * 1024]),
                        ],
                    ],
                ],
            ],
        ];
    }

    protected static function feedback(): array
    {
        return [
            'slug' => 'customer-feedback',
            'name' => 'Customer Feedback',
            'description' => 'A net-promoter style survey with a rating scale.',
            'icon' => '⭐',
            'schema' => [
                'title' => 'Customer Feedback',
                'description' => 'Tell us how we are doing — it takes less than a minute.',
                'sections' => [
                    [
                        'title' => 'Your experience',
                        'fields' => [
                            self::field('rating', 'Overall rating', 'rating', ['required' => true]),
                            self::field('radio', 'Would you recommend us?', 'recommend', [
                                'required' => true,
                                'options' => ['Definitely', 'Probably', 'Not sure', 'Probably not', 'Definitely not'],
                            ]),
                            self::field('textarea', 'What did you like most?', 'likes'),
                            self::field('textarea', 'How could we improve?', 'improvements'),
                        ],
                    ],
                    [
                        'title' => 'About you',
                        'fields' => [
                            self::field('text', 'Name', 'name'),
                            self::field('email', 'Email (optional)', 'email'),
                        ],
                    ],
                ],
            ],
        ];
    }

    protected static function field(string $type, string $label, string $key, array $opts = []): array
    {
        return [
            'id' => 'fld_'.str()->random(8),
            'type' => $type,
            'label' => $label,
            'key' => $key,
            'placeholder' => $opts['placeholder'] ?? null,
            'help_text' => $opts['help_text'] ?? null,
            'default' => $opts['default'] ?? null,
            'required' => (bool) ($opts['required'] ?? false),
            'options' => array_map(fn ($o) => ['label' => $o, 'value' => $o], $opts['options'] ?? []),
            'validation' => array_merge(FieldTypeRegistry::defaultConfig(), [
                'min' => $opts['min'] ?? null,
                'max' => $opts['max'] ?? null,
                'mimes' => $opts['mimes'] ?? [],
                'max_size' => $opts['max_size'] ?? null,
            ]),
            'conditions' => [],
        ];
    }
}
