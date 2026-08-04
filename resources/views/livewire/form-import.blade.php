<div class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Import from Word / Excel</h1>
            @if ($preview)
                <a href="{{ route('import.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Upload another file</a>
            @endif
        </div>

        @if ($error)
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md">{{ $error }}</div>
        @endif

        {{-- Upload --}}
        @if (! $preview || $preview->status === 'failed')
            <div class="bg-white rounded-lg shadow p-6">
                @if ($preview && $preview->status === 'failed')
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md">
                        Parsing failed: {{ $preview->error }}
                    </div>
                @endif

                <form wire:submit="handleUpload" class="space-y-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Upload a <code>.docx</code> or <code>.xlsx</code> file
                    </label>

                    <div class="flex items-center gap-3">
                        <input
                            type="file"
                            wire:model="importFile"
                            accept=".docx,.xlsx"
                            class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-semibold hover:file:bg-indigo-100"
                        >
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Upload & parse
                        </button>
                    </div>
                    @error('importFile') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </form>

                <div class="mt-5 text-xs text-gray-400 space-y-1">
                    <p><strong>Word:</strong> Heading styles become sections, questions become fields, bullet / checkbox lists become options.</p>
                    <p><strong>Excel (structured):</strong> row 1 = <code>question | type | required | options | section</code>, one field per row.</p>
                    <p><strong>Excel (plain):</strong> row 1 = field labels, one field per column.</p>
                    <p>Files are parsed in a background job — nothing blocks the upload.</p>
                </div>
            </div>
        @endif

        {{-- Status --}}
        @if ($preview && ($preview->status === 'queued' || $preview->status === 'processing'))
            <div class="bg-white rounded-lg shadow p-6" wire:poll.1s>
                <div class="flex items-center gap-3 text-sm text-gray-500">
                    <div class="h-4 w-4 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                    Parsing {{ $preview->original_filename }} in the background…
                </div>
            </div>
        @endif

        {{-- Preview / mapping --}}
        @if ($preview && $preview->status === 'completed')
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-900">Preview & mapping</h2>
                    <button wire:click="createForm" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Create form & open builder →
                    </button>
                </div>

                @if ($preview->warnings)
                    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-md">
                        <strong>Warnings</strong>
                        <ul class="list-disc list-inside mt-1">
                            @foreach ($preview->warnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="text-xs text-gray-400 mb-4">
                    Review the detected field types below and fix anything wrong before committing.
                </p>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Form title</label>
                        <input
                            type="text"
                            wire:model="draft.title"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        >
                    </div>

                    @foreach ($draft['sections'] ?? [] as $si => $section)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <input
                                type="text"
                                wire:model="draft.sections.{{ $si }}.title"
                                class="w-full mb-3 font-medium text-gray-800 border-0 focus:ring-0 focus:border-transparent bg-transparent"
                                placeholder="Section title"
                            >

                            <div class="space-y-2">
                                @foreach ($section['fields'] ?? [] as $fi => $field)
                                    <div class="grid grid-cols-1 md:grid-cols-[1fr_160px_90px_auto] gap-2 items-center border border-gray-100 rounded-md p-2">
                                        <input
                                            type="text"
                                            wire:model="draft.sections.{{ $si }}.fields.{{ $fi }}.label"
                                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Field label"
                                        >

                                        <select
                                            wire:model="draft.sections.{{ $si }}.fields.{{ $fi }}.type"
                                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            @foreach ($fieldTypes as $type => $meta)
                                                <option value="{{ $type }}">{{ $meta['label'] }}</option>
                                            @endforeach
                                        </select>

                                        <label class="flex items-center gap-1.5 text-xs text-gray-600 whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                wire:model="draft.sections.{{ $si }}.fields.{{ $fi }}.required"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            >
                                            Required
                                        </label>

                                        <div class="flex items-center gap-2">
                                            @if ($field['confidence'] ?? null)
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium {{ $field['confidence'] === 'high' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-700' }}">
                                                    {{ $field['confidence'] }}
                                                </span>
                                            @endif
                                        </div>

                                        @if (in_array($field['type'] ?? null, ['dropdown', 'radio', 'checkbox'], true))
                                            <div class="md:col-span-4">
                                                <textarea
                                                    rows="1"
                                                    placeholder="Options separated by commas"
                                                    wire:input="setOptions({{ $si }}, {{ $fi }}, $event.target.value)"
                                                    class="w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >{{ implode(', ', $field['options'] ?? []) }}</textarea>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <button wire:click="createForm" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Create form & open builder →
                    </button>
                </div>
            </div>
        @endif

        {{-- History --}}
        @if ($history->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">Recent imports</h2>
                <div class="bg-white rounded-lg shadow divide-y divide-gray-100">
                    @foreach ($history as $item)
                        <a href="{{ route('import.show', ['preview' => $item->id]) }}" wire:navigate
                           class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-800 truncate">{{ $item->original_filename }}</p>
                                <p class="text-xs text-gray-400">{{ $item->created_at->diffForHumans() }} · {{ $item->file_type }}</p>
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
