<div class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <a href="{{ route('forms.submissions', $form) }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Back to {{ $form->title }}</a>
                <h1 class="text-2xl font-semibold text-gray-900 mt-1">Analytics — {{ $form->title }}</h1>
            </div>
            <a href="{{ route('forms.edit', $form) }}" wire:navigate class="px-4 py-2 rounded-md border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">Edit form</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total responses</p>
                <p class="text-3xl font-semibold text-gray-900 mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Questions answered (avg)</p>
                <p class="text-3xl font-semibold text-gray-900 mt-1">{{ $stats['averageAnswered'] }}<span class="text-base text-gray-400"> / {{ $stats['fieldCount'] }}</span></p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. filling time</p>
                <p class="text-3xl font-semibold text-gray-900 mt-1">{{ floor($stats['averageFillingSeconds'] / 60) }}m {{ $stats['averageFillingSeconds'] % 60 }}s</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Last {{ $days }} days</p>
                <p class="text-3xl font-semibold text-gray-900 mt-1">{{ $stats['daily']->sum() }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-semibold text-gray-900">Responses per day</h2>
                <div class="flex items-center gap-1 text-sm">
                    <span class="text-gray-500 mr-1">Range:</span>
                    @foreach ([7, 14, 30] as $option)
                        <button
                            wire:click="$set('days', {{ $option }})"
                            class="px-2.5 py-1 rounded-md {{ $days === $option ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}"
                        >{{ $option }}d</button>
                    @endforeach
                </div>
            </div>

            @if ($stats['maxDaily'] === 0)
                <p class="text-sm text-gray-400 py-8 text-center">No responses in this period yet.</p>
            @else
                <div class="flex items-end gap-1 sm:gap-2 h-40">
                    @for ($i = $days - 1; $i >= 0; $i--)
                        @php
                            $day = now()->subDays($i)->toDateString();
                            $count = $stats['daily'][$day] ?? 0;
                            $height = $stats['maxDaily'] > 0 ? max(2, round(($count / $stats['maxDaily']) * 100)) : 0;
                            $label = now()->subDays($i)->format('M j');
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-1 group relative" title="{{ $label }}: {{ $count }}">
                            <span class="text-[10px] text-gray-400 absolute -top-5 {{ $count === 0 ? 'hidden' : '' }}">{{ $count }}</span>
                            <div class="w-full {{ $count > 0 ? 'bg-indigo-500' : 'bg-gray-100' }} rounded-t" style="height: {{ $height }}%"></div>
                            @if ($days <= 14)
                                <span class="text-[10px] text-gray-400">{{ $label }}</span>
                            @endif
                        </div>
                    @endfor
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 pt-6 pb-2">
                <h2 class="font-semibold text-gray-900">Drop-off by question</h2>
                <p class="text-sm text-gray-500 mt-1">Lowest completion rates first — where respondents give up.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Question</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Answered</th>
                            <th class="px-6 py-3 w-1/2 text-xs font-medium text-gray-500 uppercase">Completion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($stats['perField'] as $row)
                            <tr>
                                <td class="px-6 py-3 text-gray-800">{{ $row['label'] }}</td>
                                <td class="px-6 py-3 text-right text-gray-500 whitespace-nowrap">{{ $row['answered'] }} / {{ $stats['total'] }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div
                                                class="h-2 rounded-full {{ $row['rate'] >= 80 ? 'bg-green-500' : ($row['rate'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                                                style="width: {{ $row['rate'] }}%"
                                            ></div>
                                        </div>
                                        <span class="w-12 text-right text-xs text-gray-600">{{ $row['rate'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-gray-400">No questions in this form yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
