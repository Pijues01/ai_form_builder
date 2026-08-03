<?php

use App\Http\Controllers\SubmissionExportController;
use App\Livewire\AiGenerate;
use App\Livewire\FormImport;
use App\Livewire\Forms\FormBuilder;
use App\Livewire\Forms\FormList;
use App\Livewire\Forms\FormSubmissions;
use App\Livewire\Forms\FormVersions;
use App\Livewire\PublicForm;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/forms');

Route::get('/forms', FormList::class)
    ->middleware(['auth'])
    ->name('forms.index');

Route::get('/forms/{form}/edit', FormBuilder::class)
    ->middleware(['auth'])
    ->name('forms.edit');

Route::get('/forms/{form}/submissions', FormSubmissions::class)
    ->middleware(['auth'])
    ->name('forms.submissions');

Route::get('/forms/{form}/submissions/export', [SubmissionExportController::class, 'csv'])
    ->middleware(['auth'])
    ->name('forms.submissions.export');

Route::get('/import', FormImport::class)
    ->middleware(['auth'])
    ->name('import.index');

Route::get('/import/{preview}', FormImport::class)
    ->middleware(['auth'])
    ->name('import.show');

Route::get('/ai', AiGenerate::class)
    ->middleware(['auth'])
    ->name('ai.index');

Route::get('/ai/{generation}', AiGenerate::class)
    ->middleware(['auth'])
    ->name('ai.show');

Route::get('/forms/{form}/versions', FormVersions::class)
    ->middleware(['auth'])
    ->name('forms.versions');

Route::get('/f/{form:slug}', PublicForm::class)->name('forms.fill');

Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');
Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

require __DIR__.'/auth.php';
