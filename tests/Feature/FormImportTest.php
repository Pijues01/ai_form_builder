<?php

namespace Tests\Feature;

use App\Jobs\ImportFileJob;
use App\Livewire\FormImport;
use App\Models\Form;
use App\Models\ImportPreview;
use App\Models\User;
use App\Services\Import\FormImportService;
use App\Services\Schema\FormSchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FormImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_import_page_requires_auth(): void
    {
        $this->get('/import')->assertRedirect('/login');
    }

    public function test_upload_creates_preview_and_dispatches_job(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->createWithContent(
            'conference-feedback.docx',
            file_get_contents(storage_path('import-samples/conference-feedback.docx')),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        Livewire::actingAs($this->user)
            ->test(FormImport::class)
            ->set('importFile', $file)
            ->call('handleUpload');

        $preview = ImportPreview::where('user_id', $this->user->id)->first();

        $this->assertNotNull($preview);
        $this->assertSame('queued', $preview->status);
        $this->assertSame('docx', $preview->file_type);
        $this->assertSame('conference-feedback.docx', $preview->original_filename);

        Queue::assertPushed(ImportFileJob::class);
    }

    public function test_upload_rejects_bad_mime(): void
    {
        Livewire::actingAs($this->user)
            ->test(FormImport::class)
            ->set('importFile', UploadedFile::fake()->create('notes.txt', 100))
            ->call('handleUpload')
            ->assertHasErrors('importFile');

        $this->assertSame(0, ImportPreview::count());
    }

    public function test_job_parses_docx(): void
    {
        $preview = $this->queuePreview('docx', 'conference-feedback.docx');

        (new ImportFileJob($preview))->handle(app(FormImportService::class));

        $preview->refresh();

        $this->assertSame('completed', $preview->status);
        $this->assertNull($preview->error);
        $this->assertSame('Conference Feedback Form', $preview->result['title']);
        $this->assertNotEmpty($preview->result['sections']);

        $types = array_column(
            array_merge(...array_map(fn ($s) => $s['fields'], $preview->result['sections'])),
            'type'
        );

        $this->assertContains('email', $types);
        $this->assertContains('phone', $types);
        $this->assertContains('dropdown', $types);
        $this->assertContains('rating', $types);
        $this->assertContains('url', $types);
    }

    public function test_job_parses_structured_xlsx(): void
    {
        $preview = $this->queuePreview('xlsx', 'registration-structured.xlsx');

        (new ImportFileJob($preview))->handle(app(FormImportService::class));

        $preview->refresh();

        $this->assertSame('completed', $preview->status);
        $this->assertSame('Registration Structured', $preview->result['title']);
        $this->assertCount(2, $preview->result['sections']);
        $this->assertSame(['Contact', 'Job Preferences'], array_column($preview->result['sections'], 'title'));
    }

    public function test_job_parses_plain_header_xlsx(): void
    {
        $preview = $this->queuePreview('xlsx', 'attendee-plain-header.xlsx');

        (new ImportFileJob($preview))->handle(app(FormImportService::class));

        $preview->refresh();

        $this->assertSame('completed', $preview->status);
        $this->assertNotEmpty($preview->warnings);

        $labels = array_column($preview->result['sections'][0]['fields'], 'label');
        $this->assertContains('Email', $labels);
        $this->assertContains('Years of experience', $labels);
    }

    public function test_import_job_reports_unparseable_file(): void
    {
        $dir = Storage::disk('local')->path('imports/'.$this->user->id);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($dir.'/broken.docx', 'not a zip file');

        $preview = ImportPreview::create([
            'user_id' => $this->user->id,
            'original_filename' => 'broken.docx',
            'file_type' => 'docx',
            'disk' => 'local',
            'file_path' => 'imports/'.$this->user->id.'/broken.docx',
            'status' => 'queued',
        ]);

        (new ImportFileJob($preview))->handle(app(FormImportService::class));

        $preview->refresh();

        $this->assertSame('failed', $preview->status);
        $this->assertNotNull($preview->error);
    }

    public function test_create_form_commits_preview(): void
    {
        $preview = $this->queuePreview('docx', 'conference-feedback.docx');
        (new ImportFileJob($preview))->handle(app(FormImportService::class));
        $preview->refresh();

        Livewire::actingAs($this->user)
            ->test(FormImport::class, ['preview' => $preview->id])
            ->call('createForm')
            ->assertHasNoErrors()
            ->assertRedirect();

        $form = Form::where('user_id', $this->user->id)->first();

        $this->assertNotNull($form);
        $this->assertSame('Conference Feedback Form', $form->title);
        $this->assertSame(1, $form->schema_version);
        $this->assertCount(1, $form->versions);
        $this->assertSame($form->id, $preview->fresh()->form_id);

        $check = app(FormSchemaValidator::class)->validate($form->schema);
        $this->assertTrue($check['valid'], implode(' | ', $check['errors']));
    }

    public function test_other_user_does_not_see_preview(): void
    {
        $preview = $this->queuePreview('docx', 'conference-feedback.docx');
        (new ImportFileJob($preview))->handle(app(FormImportService::class));

        $other = User::factory()->create();

        $response = $this->actingAs($other)
            ->get('/import/'.$preview->id)
            ->assertOk();

        $response->assertDontSee('Conference Feedback Form');
    }

    private function queuePreview(string $type, string $name): ImportPreview
    {
        $dir = Storage::disk('local')->path('imports/'.$this->user->id);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        copy(storage_path('import-samples/'.$name), $dir.'/'.$name);

        return ImportPreview::create([
            'user_id' => $this->user->id,
            'original_filename' => $name,
            'file_type' => $type,
            'disk' => 'local',
            'file_path' => 'imports/'.$this->user->id.'/'.$name,
            'status' => 'queued',
        ]);
    }
}
