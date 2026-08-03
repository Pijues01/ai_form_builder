<?php

namespace Tests\Feature;

use App\Livewire\PublicForm;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicFormTest extends TestCase
{
    use RefreshDatabase;

    private function makeForm(array $fields): Form
    {
        $user = User::factory()->create();

        return Form::create([
            'user_id' => $user->id,
            'title' => 'Public Form',
            'slug' => 'public-'.uniqid(),
            'schema' => [
                'title' => 'Public Form',
                'description' => '',
                'sections' => [
                    ['id' => 'sec_1', 'title' => 'S', 'fields' => $fields],
                ],
            ],
            'status' => 'published',
        ]);
    }

    private function field(array $overrides): array
    {
        return array_merge([
            'id' => 'fld_'.uniqid(),
            'type' => 'text',
            'label' => 'Field',
            'key' => 'field',
            'placeholder' => null,
            'help_text' => null,
            'default' => null,
            'required' => false,
            'options' => [],
            'validation' => ['min' => null, 'max' => null, 'min_length' => null, 'max_length' => null, 'step' => null, 'pattern' => null, 'min_selections' => null, 'max_selections' => null, 'mimes' => [], 'max_size' => null, 'max_files' => null],
            'conditions' => [],
        ], $overrides);
    }

    public function test_draft_form_is_not_public(): void
    {
        $user = User::factory()->create();
        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Draft',
            'slug' => 'draft-'.uniqid(),
            'schema' => ['title' => 'Draft', 'description' => '', 'sections' => []],
            'status' => 'draft',
        ]);

        $this->get('/f/'.$form->slug)->assertNotFound();
    }

    public function test_public_form_renders(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Name', 'key' => 'name', 'required' => true]),
        ]);

        $this->get('/f/'.$form->slug)->assertOk()->assertSee('Name');
    }

    public function test_submission_is_validated_against_schema(): void
    {
        $form = $this->makeForm([
            $this->field(['type' => 'email', 'label' => 'Email', 'key' => 'email', 'required' => true]),
        ]);

        Livewire::test(PublicForm::class, ['form' => $form])
            ->set('startedAt', time() - 10)
            ->set('values.email', 'not-an-email')
            ->call('submit')
            ->assertHasErrors(['values.email']);

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_valid_submission_is_stored(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Name', 'key' => 'name', 'required' => true]),
        ]);

        Livewire::test(PublicForm::class, ['form' => $form])
            ->set('startedAt', time() - 10)
            ->set('values.name', 'Rahul')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('form_submissions', 1);

        $submission = FormSubmission::first();
        $this->assertEquals('Rahul', $submission->data['name']);
        $this->assertStringContainsString('Rahul', $submission->searchable);
    }

    public function test_conditional_field_hidden_without_trigger(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Have car', 'key' => 'has_car', 'type' => 'radio', 'options' => ['Yes', 'No']]),
            $this->field([
                'label' => 'Car model',
                'key' => 'car_model',
                'conditions' => [['field' => 'has_car', 'operator' => 'equals', 'value' => 'Yes']],
            ]),
        ]);

        Livewire::test(PublicForm::class, ['form' => $form])
            ->set('values.has_car', 'No')
            ->call('submit')
            ->assertHasNoErrors(['values.car_model']);
    }
}
