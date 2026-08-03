<?php

namespace Tests\Feature;

use App\Jobs\GenerateFormJob;
use App\Livewire\AiGenerate;
use App\Models\AiGeneration;
use App\Models\Form;
use App\Models\User;
use App\Services\AI\FormGenerator;
use App\Services\Schema\FormSchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class AiGenerateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_ai_page_requires_auth(): void
    {
        $this->get('/ai')->assertRedirect('/login');
    }

    public function test_generation_creates_record_and_dispatches_job(): void
    {
        Queue::fake();

        Livewire::actingAs($this->user)
            ->test(AiGenerate::class)
            ->set('prompt', 'Internship application with resume upload')
            ->call('generate');

        $generation = AiGeneration::where('user_id', $this->user->id)->first();

        $this->assertNotNull($generation);
        $this->assertSame('queued', $generation->status);
        $this->assertSame('create', $generation->mode);
        $this->assertSame('Internship application with resume upload', $generation->prompt);

        Queue::assertPushed(GenerateFormJob::class);
    }

    public function test_generate_requires_prompt(): void
    {
        Livewire::actingAs($this->user)
            ->test(AiGenerate::class)
            ->call('generate')
            ->assertHasErrors('prompt');

        $this->assertSame(0, AiGeneration::count());
    }

    public function test_job_completes_with_schema_valid_result(): void
    {
        $generation = AiGeneration::create([
            'user_id' => $this->user->id,
            'mode' => 'create',
            'prompt' => 'Customer feedback form with name, email, rating and a message box',
            'status' => 'queued',
        ]);

        (new GenerateFormJob($generation))->handle(
            app(FormGenerator::class),
            app(FormSchemaValidator::class)
        );

        $generation->refresh();

        $this->assertSame('completed', $generation->status);
        $this->assertNull($generation->error);
        $this->assertNotNull($generation->result);
        $this->assertNotEmpty($generation->result['schema']['sections']);

        $check = app(FormSchemaValidator::class)->validate($generation->result['schema']);
        $this->assertTrue($check['valid'], implode(' | ', $check['errors']));
    }

    public function test_apply_creates_form_in_create_mode(): void
    {
        $generation = AiGeneration::create([
            'user_id' => $this->user->id,
            'mode' => 'create',
            'prompt' => 'Event registration form',
            'status' => 'completed',
            'result' => ['schema' => $this->validSchema('Event Registration')],
        ]);

        Livewire::actingAs($this->user)
            ->test(AiGenerate::class, ['generation' => $generation->id])
            ->call('apply')
            ->assertHasNoErrors()
            ->assertRedirect();

        $form = Form::where('user_id', $this->user->id)->first();

        $this->assertNotNull($form);
        $this->assertSame('Event Registration', $form->title);
        $this->assertSame($form->id, $generation->fresh()->form_id);
    }

    public function test_apply_edits_existing_form_in_edit_mode(): void
    {
        $form = Form::create([
            'user_id' => $this->user->id,
            'title' => 'Old Form',
            'slug' => 'old-form',
            'schema' => ['title' => 'Old Form', 'description' => '', 'sections' => []],
            'schema_version' => 1,
        ]);

        $generation = AiGeneration::create([
            'user_id' => $this->user->id,
            'form_id' => $form->id,
            'mode' => 'edit',
            'prompt' => 'Make phone required',
            'status' => 'completed',
            'result' => ['schema' => $this->validSchema('New Title')],
        ]);

        Livewire::actingAs($this->user)
            ->test(AiGenerate::class, ['generation' => $generation->id])
            ->call('apply')
            ->assertHasNoErrors()
            ->assertRedirect();

        $form->refresh();

        $this->assertSame('New Title', $form->title);
        $this->assertSame(2, $form->schema_version);
        $this->assertCount(1, $form->versions);
    }

    public function test_other_user_does_not_see_generation(): void
    {
        $generation = AiGeneration::create([
            'user_id' => $this->user->id,
            'mode' => 'create',
            'prompt' => 'Test secret prompt',
            'status' => 'completed',
            'result' => ['schema' => $this->validSchema('X')],
        ]);

        $other = User::factory()->create();

        $response = $this->actingAs($other)
            ->get('/ai/'.$generation->id)
            ->assertOk();

        $response->assertDontSee('Test secret prompt');
    }

    private function validSchema(string $title): array
    {
        return [
            'title' => $title,
            'description' => '',
            'sections' => [
                [
                    'title' => 'Contact',
                    'fields' => [
                        [
                            'type' => 'email',
                            'label' => 'Email',
                            'key' => 'email',
                            'required' => true,
                            'options' => [],
                            'validation' => [],
                        ],
                    ],
                ],
            ],
        ];
    }
}
