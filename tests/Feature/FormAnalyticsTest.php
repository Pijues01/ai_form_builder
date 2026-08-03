<?php

namespace Tests\Feature;

use App\Livewire\Forms\FormAnalytics;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Form $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->form = Form::create([
            'user_id' => $this->user->id,
            'title' => 'Analytics Form',
            'slug' => 'analytics-'.uniqid(),
            'schema' => [
                'title' => 'Analytics Form',
                'description' => '',
                'sections' => [
                    [
                        'id' => 'sec_1',
                        'title' => 'S',
                        'fields' => [
                            ['id' => 'fld_1', 'type' => 'text', 'label' => 'Name', 'key' => 'name', 'placeholder' => null, 'help_text' => null, 'default' => null, 'required' => true, 'options' => [], 'validation' => ['min' => null, 'max' => null, 'min_length' => null, 'max_length' => null, 'step' => null, 'pattern' => null, 'min_selections' => null, 'max_selections' => null, 'mimes' => [], 'max_size' => null, 'max_files' => null], 'conditions' => []],
                            ['id' => 'fld_2', 'type' => 'email', 'label' => 'Email', 'key' => 'email', 'placeholder' => null, 'help_text' => null, 'default' => null, 'required' => true, 'options' => [], 'validation' => ['min' => null, 'max' => null, 'min_length' => null, 'max_length' => null, 'step' => null, 'pattern' => null, 'min_selections' => null, 'max_selections' => null, 'mimes' => [], 'max_size' => null, 'max_files' => null], 'conditions' => []],
                        ],
                    ],
                ],
            ],
            'status' => 'published',
        ]);
    }

    private function submit(array $data, array $metadata = []): void
    {
        FormSubmission::create([
            'form_id' => $this->form->id,
            'data' => $data,
            'searchable' => implode(' ', $data),
            'ip' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'metadata' => array_merge(['filling_time_seconds' => 42], $metadata),
        ]);
    }

    public function test_analytics_page_requires_ownership(): void
    {
        $other = User::factory()->create();

        Livewire::actingAs($other)
            ->test(FormAnalytics::class, ['form' => $this->form])
            ->assertStatus(403);
    }

    public function test_analytics_shows_total_and_drop_off(): void
    {
        $this->submit(['name' => 'A', 'email' => 'a@example.com']);
        $this->submit(['name' => 'B', 'email' => 'b@example.com']);
        $this->submit(['name' => 'C', 'email' => '']);

        Livewire::actingAs($this->user)
            ->test(FormAnalytics::class, ['form' => $this->form])
            ->assertSee('3')
            ->assertSee('Name')
            ->assertSee('Email')
            ->assertSee('100%')
            ->assertSee('66.7%');
    }

    public function test_analytics_shows_average_filling_time(): void
    {
        $this->submit(['name' => 'A', 'email' => 'a@example.com'], ['filling_time_seconds' => 75]);

        Livewire::actingAs($this->user)
            ->test(FormAnalytics::class, ['form' => $this->form])
            ->assertSee('1m 15s');
    }

    public function test_analytics_with_no_submissions_renders_gracefully(): void
    {
        Livewire::actingAs($this->user)
            ->test(FormAnalytics::class, ['form' => $this->form])
            ->assertSee('No responses in this period yet.')
            ->assertSee('0');
    }
}
