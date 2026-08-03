<div class="py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('forms.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Back to forms</a>
            <h1 class="text-2xl font-semibold text-gray-900 mt-1">{{ $form->title }} — Version history</h1>
            <p class="text-sm text-gray-500 mt-1">Current version: v{{ $form->schema_version }}</p>
        </div>

        @if (session()->has('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-md">{{ session('status') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow divide-y divide-gray-100">
            @forelse ($form->versions as $version)
                <div class="p-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-800">v{{ $version->version }}</span>
                            @if ($version->version === $form->schema_version)
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">current</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500">{{ $version->note }} · {{ $version->created_at->diffForHumans() }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $version->schema['sections'] ? count($version->schema['sections']) : 0 }} sections
                            · {{ collect($version->schema['sections'] ?? [])->sum(fn ($s) => count($s['fields'] ?? [])) }} fields
                        </p>
                    </div>
                    @if ($version->version !== $form->schema_version)
                        <button
                            wire:click="restore({{ $version->id }})"
                            wire:confirm="Restore this version? It will be saved as a new version on top of the current one."
                            class="px-3 py-1.5 rounded-md border border-indigo-200 text-sm text-indigo-700 hover:bg-indigo-50"
                        >Restore</button>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-gray-400">No versions recorded yet.</div>
            @endforelse
        </div>
    </div>
</div>
