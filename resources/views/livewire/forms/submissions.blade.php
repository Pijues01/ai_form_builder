<div class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <a href="{{ route('forms.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Back to forms</a>
                <h1 class="text-2xl font-semibold text-gray-900 mt-1">{{ $form->title }} — Responses</h1>
            </div>
            <div class="flex items-center gap-3">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search responses..."
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                <a
                    href="{{ route('forms.submissions.export', ['form' => $form, 'search' => $search]) }}"
                    class="px-4 py-2 rounded-md bg-green-600 text-sm font-medium text-white hover:bg-green-500"
                >Export CSV</a>
                <a href="{{ route('forms.edit', $form) }}" wire:navigate class="px-4 py-2 rounded-md border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">Edit form</a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted at</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Preview</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($submissions as $submission)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $submission->created_at->format('M j, Y H:i') }}</td>
                                <td class="px-4 py-3 max-w-md">
                                    <div class="truncate text-gray-600">
                                        @php $first = array_slice($submission->data ?? [], 0, 3, true); @endphp
                                        @foreach ($first as $k => $v)
                                            <span class="text-gray-400">{{ $k }}:</span> {{ is_array($v) ? implode(', ', $v) : (strlen((string)$v) > 40 ? substr((string)$v, 0, 40).'…' : $v) }}
                                            @if (!$loop->last)<span class="text-gray-300"> · </span>@endif
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $submission->ip }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        class="text-indigo-600 hover:text-indigo-800 text-xs font-medium"
                                        x-data="{ open: false, data: {{ json_encode($submission->data) }} }"
                                        @click="open = true"
                                    >View</button>

                                    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="open = false">
                                        <div class="bg-white rounded-lg shadow-xl w-full max-w-xl p-5">
                                            <div class="flex items-center justify-between mb-3">
                                                <h3 class="font-semibold text-gray-800">Submission #{{ $submission->id }}</h3>
                                                <button @click="open = false" class="text-gray-400 hover:text-gray-600">✕</button>
                                            </div>
                                            <dl class="space-y-2 text-sm">
                                                <template x-for="(value, key) in data" :key="key">
                                                    <div class="flex gap-2">
                                                        <dt class="w-40 shrink-0 font-medium text-gray-500" x-text="key"></dt>
                                                        <dd class="text-gray-800 break-all" x-text="Array.isArray(value) ? value.join(', ') : value"></dd>
                                                    </div>
                                                </template>
                                            </dl>
                                            <div class="mt-4 text-xs text-gray-400">
                                                IP: {{ $submission->ip }} · UA: {{ substr((string) $submission->user_agent, 0, 80) }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center text-gray-400">
                                    @if ($search)
                                        No responses matching "{{ $search }}".
                                    @else
                                        No responses yet.
                                    @endif
                                    <a href="{{ $form->publicUrl() }}" target="_blank" class="block mt-2 text-indigo-600 hover:underline">Open the public form →</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $submissions->links() }}
        </div>
    </div>
</div>
