<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\User;
use App\Services\Schema\FieldTypeRegistry;
use App\Services\Schema\FormSchemaValidator;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Admin',
                'password' => bcrypt('password'),
            ]
        );

        if (Form::where('slug', 'internship-application')->exists()) {
            return;
        }

        $schema = [
            'title' => 'Internship Application',
            'description' => 'Tell us about yourself and why you want to join our internship program.',
            'sections' => [
                [
                    'id' => 'sec_personal',
                    'title' => 'Personal Details',
                    'fields' => [
                        array_merge(FieldTypeRegistry::newField('text', 'Full Name'), ['key' => 'full_name', 'required' => true, 'placeholder' => 'e.g. Rahul Sharma']),
                        array_merge(FieldTypeRegistry::newField('email', 'Email Address'), ['key' => 'email', 'required' => true, 'placeholder' => 'you@example.com']),
                        array_merge(FieldTypeRegistry::newField('phone', 'Phone Number'), ['key' => 'phone', 'placeholder' => '+91 98xxxxxx']),
                        array_merge(FieldTypeRegistry::newField('date', 'Date of Birth'), ['key' => 'dob', 'help_text' => 'Optional, used for eligibility checks']),
                    ],
                ],
                [
                    'id' => 'sec_education',
                    'title' => 'Education & Skills',
                    'fields' => [
                        array_merge(FieldTypeRegistry::newField('text', 'Current College'), ['key' => 'college']),
                        array_merge(FieldTypeRegistry::newField('textarea', 'Education History'), ['key' => 'education_history', 'required' => true, 'help_text' => 'List degrees, institutions and years']),
                        array_merge(FieldTypeRegistry::newField('checkbox', 'Skills'), [
                            'key' => 'skills',
                            'options' => ['PHP', 'JavaScript', 'Python', 'UI/UX Design', 'Data Analysis'],
                            'validation' => array_merge(FieldTypeRegistry::defaultConfig(), ['min_selections' => 1]),
                        ]),
                        array_merge(FieldTypeRegistry::newField('rating', 'Programming Level'), ['key' => 'programming_level', 'required' => true, 'help_text' => '1 = beginner, 5 = expert']),
                    ],
                ],
                [
                    'id' => 'sec_resume',
                    'title' => 'Resume & Availability',
                    'fields' => [
                        array_merge(FieldTypeRegistry::newField('file', 'Resume Upload'), [
                            'key' => 'resume',
                            'required' => true,
                            'validation' => array_merge(FieldTypeRegistry::defaultConfig(), ['mimes' => ['pdf', 'doc', 'docx'], 'max_size' => 2048]),
                        ]),
                        array_merge(FieldTypeRegistry::newField('radio', 'Availability'), [
                            'key' => 'availability',
                            'required' => true,
                            'options' => ['Full-time', 'Part-time', 'Weekends only'],
                        ]),
                    ],
                ],
            ],
        ];

        $validator = app(FormSchemaValidator::class);
        $schema = $validator->normalize($schema);

        $form = Form::create([
            'user_id' => $user->id,
            'title' => $schema['title'],
            'slug' => 'internship-application',
            'description' => $schema['description'],
            'schema' => $schema,
            'schema_version' => 1,
            'status' => 'published',
            'settings' => ['confirmation_message' => 'Thanks! We will get back to you soon.'],
        ]);

        $form->versions()->create([
            'version' => 1,
            'schema' => $schema,
            'note' => 'Initial seed',
            'created_by' => $user->id,
        ]);
    }
}
