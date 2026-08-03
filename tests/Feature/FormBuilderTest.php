<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormBuilderTest extends TestCase
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
            'title' => 'Test Form',
            'slug' => 'test-form',
            'schema' => [
                'title' => 'Test Form',
                'description' => '',
                'sections' => [
                    [
                        'id' => 'sec_1',
                        'title' => 'One',
                        'fields' => [
                            [
                                'id' => 'fld_1',
                                'type' => 'text',
                                'label' => 'Name',
                                'key' => 'name',
                                'placeholder' => null,
                                'help_text' => null,
                                'default' => null,
                                'required' => true,
                                'options' => [],
                                'validation' => ['min' => null, 'max' => null, 'min_length' => null, 'max_length' => null, 'step' => null, 'pattern' => null, 'min_selections' => null, 'max_selections' => null, 'mimes' => [], 'max_size' => null, 'max_files' => null],
                                'conditions' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'status' => 'published',
        ]);
    }

    public function test_forms_page_requires_auth(): void
    {
        $this->get('/forms')->assertRedirect('/login');
    }

    public function test_owner_can_open_builder(): void
    {
        $this->actingAs($this->user)
            ->get('/forms/'.$this->form->id.'/edit')
            ->assertOk()
            ->assertSee('Test Form');
    }

    public function test_other_user_cannot_open_builder(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)
            ->get('/forms/'.$this->form->id.'/edit')
            ->assertForbidden();
    }

    public function test_submissions_page_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get('/forms/'.$this->form->id.'/submissions')
            ->assertOk();
    }

    public function test_csv_export_streams_data(): void
    {
        $this->form->submissions()->create([
            'data' => ['name' => 'Rahul'],
            'searchable' => 'Rahul',
            'ip' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/forms/'.$this->form->id.'/submissions/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
        $this->assertStringContainsString('name', $response->streamedContent());
        $this->assertStringContainsString('Rahul', $response->streamedContent());
    }
}
