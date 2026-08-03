<div class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">AI Form Generator</h1>
            @if ($generation)
                <a href="{{ route('ai.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← New generation</a>
            @endif
        </div>

        @if ($error)
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md">{{ $error }}</div>
        @endif

        @if ($notice)
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-md">{{ $notice }}</div>
        @endif

        @if (! $generation || in_array($generation->status, ['failed'], true))
            <div class="bg-white rounded-lg shadow p-6">
                <form wire:submit="generate">
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="prompt">
                        Describe the form you want
                    </label>
                    <textarea
                        id="prompt"
                        wire:model="prompt"
                        rows="4"
                        placeholder="e.g. internship application with education history, skills and resume upload"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    ></textarea>
                    @error('prompt') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mode</label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" wire:model="mode" value="create" class="text-indigo-600 focus:ring-indigo-500">
                                    Create new form
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" wire:model="mode" value="edit" class="text-indigo-600 focus:ring-indigo-500">
                                    Edit existing form
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" for="editFormId">Form to edit</label>
                            <select
                                id="editFormId"
                                wire:model="editFormId"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                {{ $mode === 'edit' ? '' : 'disabled' }}
                            >
                                <option value="">Select a form…</option>
                                @foreach ($forms as $form)
                                    <option value="{{ $form->id }}">{{ $form->title }} (v{{ $form->schema_version }})</option>
                                @endforeach
                            </select>
                            @error('editFormId') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-5">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            ✨ Generate form
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-xs text-gray-400">
                    Driver: <code>{{ config('ai.driver') }}</code> · Model: <code>{{ config('ai.model') }}</code>
                </div>
            </div>
        @endif

        {{-- Active generation status --}}
        @if ($generation)
            <div class="bg-white rounded-lg shadow p-6" @if ($polling) wire:poll.1s @endif>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-900">
                        {{ $generation->mode === 'edit' ? 'Editing form' : 'Generating form' }}
                        <span class="ml-2 text-sm font-normal text-gray-500">#{{ $generation->id }}</span>
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $generation->status === 'completed' ? 'bg-green-100 text-green-800' : ($generation->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                        {{ ucfirst($generation->status) }}
                    </span>
                </div>

                <p class="text-sm text-gray-600 mb-4">
                    <span class="text-gray-400">Prompt:</span> {{ $generation->prompt }}
                </p>

                @if ($generation->status === 'queued' || $generation->status === 'processing')
                    <div class="flex items-center gap-3 text-sm text-gray-500">
                        <div class="h-4 w-4 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                        Running the AI in the background… this page updates automatically.
                    </div>
                @endif

                @if ($generation->status === 'failed')
                    <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md">
                        Generation failed: {{ $generation->error }}
                    </div>
                    <div class="mt-4">
                        <button wire:click="retry" class="px-4 py-2 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
                            ↻ Retry
                        </button>
                    </div>
                @endif

                @if ($generation->status === 'completed')
                    <div class="mb-4 flex flex-wrap gap-4 text-xs text-gray-500">
                        <span>Model: <strong>{{ $generation->model ?? '—' }}</strong></span>
                        <span>Tokens: <strong>{{ number_format($generation->tokens_total ?? 0) }}</strong></span>
                        <span>Latency: <strong>{{ $generation->latency_ms ?? 0 }} ms</strong></span>
                        <span>Repair attempts: <strong>{{ $generation->repair_attempts }}</strong></span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Generated schema</h3>
                            <pre class="p-3 bg-gray-50 border border-gray-200 rounded-md text-xs text-gray-700 overflow-auto max-h-80 whitespace-pre-wrap">{{ json_encode($generation->result['schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Preview</h3>
                            <div class="space-y-4 max-h-80 overflow-auto pr-1">
                                <div class="text-lg font-semibold text-gray-900">{{ $generation->result['schema']['title'] ?? 'Untitled' }}</div>
                                @foreach (($generation->result['schema']['sections'] ?? []) as $section)
                                    <div>
                                        <div class="text-sm font-medium text-gray-700 border-b border-gray-200 pb-1 mb-2">{{ $section['title'] ?? 'Section' }}</div>
                                        <ul class="space-y-1">
                                            @foreach ($section['fields'] ?? [] as $field)
                                                <li class="flex items-center gap-2 text-sm text-gray-600">
                                                    <span class="w-24 shrink-0 text-xs font-medium text-indigo-600 uppercase">{{ $field['type'] }}</span>
                                                    <span>{{ $field['label'] }}</span>
                                                    @if ($field['required'] ?? false)
                                                        <span class="text-red-500">*</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4">
                                <button wire:click="apply" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ $generation->mode === 'edit' ? 'Apply to form & open builder' : 'Create form & open builder' }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Recent generations --}}
        @if ($history->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">Recent generations</h2>
                <div class="bg-white rounded-lg shadow divide-y divide-gray-100">
                    @foreach ($history as $item)
                        <a href="{{ route('ai.show', ['generation' => $item->id]) }}" wire:navigate
                           class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-800 truncate">{{ $item->prompt }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $item->mode }} · {{ $item->created_at->diffForHumans() }} ·
                                    {{ $item->model ?: '—' }}
                                </p>
                            </div>
                            <span class="ml-3 shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $item->status === 'completed' ? 'bg-green-100 text-green-800' : ($item->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
