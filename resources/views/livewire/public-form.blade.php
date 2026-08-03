<div class="max-w-2xl mx-auto px-4">
    <div class="text-center mb-6">
        <div class="inline-flex items-center gap-1 text-xs text-indigo-600 font-medium uppercase tracking-widest">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
            AI Form Builder
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $form->title }}</h1>
        @if ($form->description)
            <p class="text-gray-600 mt-1">{{ $form->description }}</p>
        @endif
    </div>

    @if ($submitted)
        <div class="bg-white rounded-xl shadow-sm border border-green-200 p-10 text-center">
            <div class="text-5xl mb-4">✅</div>
            <h2 class="text-xl font-semibold text-gray-900">{{ $settings['confirmation_message'] ?? 'Thanks! Your response has been recorded.' }}</h2>
        </div>
    @else
        <form wire:submit="submit" class="space-y-6">
            <input type="text" wire:model="honeypot" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

            @if ($error)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $error }}</div>
            @endif

            @foreach ($visibleSections as $section)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    @if ($section['title'])
                        <h2 class="font-semibold text-gray-800 mb-4">{{ $section['title'] }}</h2>
                    @endif

                    <div class="space-y-5">
                        @foreach ($section['fields'] as $field)
                            @if ($field['type'] === 'section')
                                <h3 class="pt-2 text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2">{{ $field['label'] }}</h3>
                            @elseif ($field['type'] === 'paragraph')
                                <p class="text-sm text-gray-500">{{ $field['label'] }}</p>
                            @else
                                @php
                                    $fkey = $field['key'];
                                    $accept = implode(',', array_map(fn ($m) => '.'.$m, $field['validation']['mimes'] ?? []));
                                    $inputClasses = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm';
                                @endphp

                                <div wire:key="field-{{ $field['id'] }}">
                                    <label for="f_{{ $field['id'] }}" class="block text-sm font-medium text-gray-700">
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>

                                    @if ($field['type'] === 'textarea')
                                        <textarea id="f_{{ $field['id'] }}" wire:model="values.{{ $fkey }}" rows="4" placeholder="{{ $field['placeholder'] }}" class="{{ $inputClasses }}"></textarea>
                                    @elseif ($field['type'] === 'dropdown')
                                        <select id="f_{{ $field['id'] }}" wire:model="values.{{ $fkey }}" class="{{ $inputClasses }}">
                                            <option value="">— Select —</option>
                                            @foreach ($field['options'] as $option)
                                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($field['type'] === 'radio')
                                        <div class="mt-2 space-y-2">
                                            @foreach ($field['options'] as $option)
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="radio" wire:model="values.{{ $fkey }}" value="{{ $option['value'] }}" class="text-indigo-600 focus:ring-indigo-500">
                                                    {{ $option['label'] }}
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif ($field['type'] === 'checkbox')
                                        <div class="mt-2 space-y-2">
                                            @foreach ($field['options'] as $option)
                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" wire:model="values.{{ $fkey }}" value="{{ $option['value'] }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                    {{ $option['label'] }}
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif ($field['type'] === 'file')
                                        <input type="file" id="f_{{ $field['id'] }}" wire:model="values.{{ $fkey }}" accept="{{ $accept }}" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                        <p class="text-xs text-gray-400 mt-1">
                                            @if (!empty($field['validation']['mimes'])) Allowed: {{ implode(', ', $field['validation']['mimes']) }}@endif
                                            @if (!empty($field['validation']['max_size'])) · Max {{ number_format($field['validation']['max_size'] / 1024, 1) }} MB @endif
                                        </p>
                                    @elseif ($field['type'] === 'rating')
                                        <div class="mt-2 flex gap-1">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <button type="button" wire:click="setValue('{{ $fkey }}', {{ $i }})" class="text-2xl {{ ($values[$fkey] ?? 0) >= $i ? 'text-yellow-400' : 'text-gray-300' }} hover:scale-110 transition">★</button>
                                            @endfor
                                        </div>
                                    @else
                                        <input
                                            id="f_{{ $field['id'] }}"
                                            wire:model="values.{{ $fkey }}"
                                            type="{{ $field['type'] === 'phone' ? 'tel' : $field['type'] }}"
                                            placeholder="{{ $field['placeholder'] }}"
                                            step="{{ $field['validation']['step'] ?? null }}"
                                            class="{{ $inputClasses }}"
                                        >
                                    @endif

                                    @if ($field['help_text'])
                                        <p class="mt-1 text-xs text-gray-400">{{ $field['help_text'] }}</p>
                                    @endif

                                    @error("values.{$fkey}")
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="text-center">
                <button type="submit" class="px-8 py-3 bg-indigo-600 rounded-lg font-semibold text-white hover:bg-indigo-500 shadow transition">
                    Submit
                </button>
            </div>
        </form>
    @endif

    <p class="text-center text-xs text-gray-400 mt-8">Powered by AI Form Builder</p>
</div>
