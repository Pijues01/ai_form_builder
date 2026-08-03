<?php

namespace App\Jobs;

use App\Models\ImportPreview;
use App\Services\Import\FormImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportFileJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(
        public ImportPreview $preview,
    ) {}

    /**
     * Parse the uploaded file off the web request. Large files never block the
     * browser; the preview page polls until parsing completes.
     */
    public function handle(FormImportService $service): void
    {
        $this->preview->update(['status' => 'processing']);

        try {
            $path = Storage::disk($this->preview->disk)->path($this->preview->file_path);

            $draft = $service->parse($path, $this->preview->file_type);

            $this->preview->update([
                'status' => 'completed',
                'result' => $draft,
                'warnings' => $draft['warnings'] ?? [],
                'error' => null,
            ]);

            Log::info('Import parsed', [
                'preview' => $this->preview->id,
                'file' => $this->preview->original_filename,
                'sections' => count($draft['sections'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::error('Import parsing failed', [
                'preview' => $this->preview->id,
                'error' => $e->getMessage(),
            ]);

            $this->preview->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'result' => null,
                'warnings' => [],
            ]);
        }
    }
}
