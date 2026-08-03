<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">My Forms</h1>
            <div class="flex items-center gap-3">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search forms..."
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                <a href="{{ route('templates.index') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                    Templates
                </a>
                <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    + New Form
                </button>
            </div>
        </div>

        @if (session()->has('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-md">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($forms as $form)
                <div class="bg-white rounded-lg shadow p-6 border border-gray-100 flex flex-col">
                    <div class="flex items-start justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $form->title }}</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $form->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($form->status) }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">v{{ $form->schema_version }} · {{ $form->submissions_count }} submissions</p>

                    <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-2 text-sm">
                        <a href="{{ route('forms.edit', $form) }}" wire:navigate class="px-3 py-1.5 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100">Edit</a>
                        <a href="{{ route('forms.submissions', $form) }}" wire:navigate class="px-3 py-1.5 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">Responses</a>
                        <a href="{{ route('forms.analytics', $form) }}" wire:navigate class="px-3 py-1.5 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">Analytics</a>
                        <a href="{{ route('forms.versions', $form) }}" wire:navigate class="px-3 py-1.5 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">Versions</a>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-xs">
                        <a href="{{ $form->publicUrl() }}" target="_blank" class="text-indigo-600 hover:underline truncate">
                            {{ str_replace('http://localhost', '', $form->publicUrl()) }}
                        </a>
                        <button
                            wire:click="delete({{ $form->id }})"
                            wire:confirm="Delete this form and all of its submissions? This cannot be undone."
                            class="text-red-500 hover:text-red-700"
                        >Delete</button>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-16">
                    <div class="text-4xl mb-3">📋</div>
                    <p class="text-gray-500">No forms yet.</p>
                    <button wire:click="create" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition">
                        Create your first form
                    </button>
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $forms->links() }}
        </div>
    </div>
</div>
