<?php

namespace Tests\Feature;

use App\Livewire\Templates;
use App\Models\Form;
use App\Models\User;
use App\Services\Schema\FormSchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TemplatesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_templates_page_requires_auth(): void
    {
        $this->get('/templates')->assertRedirect('/login');
    }

    public function test_templates_page_lists_templates(): void
    {
        $this->actingAs($this->user)
            ->get('/templates')
            ->assertOk()
            ->assertSee('Contact Us')
            ->assertSee('Event Registration')
            ->assertSee('Job Application')
            ->assertSee('Customer Feedback');
    }

    public function test_using_template_creates_form_and_version(): void
    {
        Livewire::actingAs($this->user)
            ->test(Templates::class)
            ->call('useTemplate', 'contact-us');

        $form = Form::first();

        $this->assertNotNull($form);
        $this->assertSame($this->user->id, $form->user_id);
        $this->assertSame('Contact Us', $form->title);
        $this->assertSame(1, $form->schema_version);
        $this->assertSame('draft', $form->status);
        $this->assertSame('Contact Us', $form->schema['title']);

        $this->assertDatabaseHas('form_versions', [
            'form_id' => $form->id,
            'version' => 1,
        ]);
    }

    public function test_created_template_form_has_valid_schema(): void
    {
        Livewire::actingAs($this->user)
            ->test(Templates::class)
            ->call('useTemplate', 'job-application');

        $form = Form::first();

        $validator = app(FormSchemaValidator::class);
        $result = $validator->validate($validator->normalize($form->schema));

        $this->assertTrue($result['valid'], implode("\n", $result['errors']));

        $keys = collect($form->schema['sections'])
            ->flatMap(fn ($s) => collect($s['fields'])->pluck('key'))
            ->all();

        $this->assertContains('resume', $keys);
        $this->assertContains('years_experience', $keys);
    }

    public function test_using_unknown_template_returns_404(): void
    {
        Livewire::actingAs($this->user)
            ->test(Templates::class)
            ->call('useTemplate', 'does-not-exist')
            ->assertStatus(404);

        $this->assertDatabaseCount('forms', 0);
    }
}
