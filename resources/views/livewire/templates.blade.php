<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <a href="{{ route('forms.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Back to forms</a>
                <h1 class="text-2xl font-semibold text-gray-900 mt-1">Start from a template</h1>
                <p class="text-sm text-gray-500 mt-1">Pick a starter form and customise it in the builder.</p>
            </div>
        </div>

        @if (session()->has('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-md">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($templates as $template)
                <div class="bg-white rounded-lg shadow border border-gray-100 p-6 flex flex-col">
                    <div class="text-3xl mb-3">{{ $template['icon'] }}</div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $template['name'] }}</h2>
                    <p class="mt-1 text-sm text-gray-500 flex-1">{{ $template['description'] }}</p>
                    <button
                        wire:click="useTemplate('{{ $template['slug'] }}')"
                        wire:loading.attr="disabled"
                        class="mt-5 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition"
                    >Use template</button>
                </div>
            @endforeach
        </div>
    </div>
</div>
