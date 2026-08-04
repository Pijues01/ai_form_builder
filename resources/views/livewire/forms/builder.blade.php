<div class="min-h-screen bg-gray-100">
    <div class="py-4 px-4 sm:px-6">
        <div class="max-w-[1400px] mx-auto">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <a href="{{ route('forms.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← Back to forms</a>

                <input
                    wire:model="title"
                    type="text"
                    placeholder="Form title"
                    class="flex-1 min-w-[220px] rounded-md border-gray-300 text-xl font-semibold text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >

                <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" wire:model="published" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Published
                </label>

                <button wire:click="toggleJson" class="px-3 py-2 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
                    JSON Editor
                </button>

                <a href="{{ route('forms.submissions', $form) }}" wire:navigate class="px-3 py-2 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
                    Responses
                </a>

                <button wire:click="save" class="px-4 py-2 rounded-md bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-500 shadow">
                    Save Form
                </button>
            </div>

            @if ($error)
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md">{{ $error }}</div>
            @endif

            @if ($notice)
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-md whitespace-pre-line">{{ $notice }}</div>
            @endif

            <div class="flex gap-4 items-start">
                {{-- Canvas --}}
                <div class="flex-1 space-y-4" data-sortable="sections">
                    <div class="bg-white rounded-lg shadow p-5">
                        <input
                            wire:model="description"
                            type="text"
                            placeholder="Form description (optional)"
                            class="w-full rounded-md border-gray-300 text-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    @forelse ($sections as $si => $section)
                        <div
                            class="bg-white rounded-lg shadow"
                            wire:key="section-{{ $section['id'] }}"
                        >
                            <div class="flex items-center gap-2 px-5 pt-4">
                                <span class="cursor-move text-gray-300 hover:text-gray-500" data-handle title="Drag to reorder section">⠿</span>
                                <input
                                    wire:model="sections.{{ $si }}.title"
                                    type="text"
                                    placeholder="Section title"
                                    class="flex-1 font-medium text-gray-800 border-0 focus:ring-0 focus:border-transparent bg-transparent"
                                >
                                <button wire:click="deleteSection({{ $si }})" wire:confirm="Delete this section and its fields?" class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                            </div>

                            <div
                                data-sortable="fields"
                                data-section="{{ $si }}"
                                class="px-5 pb-5 pt-1 space-y-2 min-h-[60px]"
                                wire:key="fields-{{ $section['id'] }}"
                            >
                                @forelse ($section['fields'] as $fi => $field)
                                    <div
                                        wire:key="field-{{ $field['id'] }}"
                                        wire:click="selectField('{{ $field['id'] }}')"
                                        class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm cursor-pointer transition
                                            {{ $selectedField['id'] ?? null === $field['id'] ? 'border-indigo-400 bg-indigo-50 ring-1 ring-indigo-300' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}"
                                    >
                                        <span class="cursor-move text-gray-300 hover:text-gray-500" data-handle title="Drag to reorder">⠿</span>
                                        <span class="w-20 shrink-0 text-xs font-medium text-indigo-600 uppercase">{{ $field['type'] }}</span>
                                        <div class="flex-1 min-w-0">
                                            <div class="truncate text-gray-800">{{ $field['label'] ?: 'Untitled field' }}</div>
                                            <div class="text-xs text-gray-400 truncate">key: {{ $field['key'] }}{{ $field['required'] ? ' · required' : '' }}</div>
                                        </div>
                                        <button wire:click.stop="duplicateField({{ $si }}, {{ $fi }})" title="Duplicate" class="text-gray-400 hover:text-gray-600">⧉</button>
                                        <button wire:click.stop="deleteField({{ $si }}, {{ $fi }})" title="Delete" class="text-red-400 hover:text-red-600">×</button>
                                    </div>
                                @empty
                                    <div class="text-center text-xs text-gray-400 border-2 border-dashed border-gray-200 rounded-md py-6">
                                        Drag a field here or click a field on the left
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-lg shadow p-8 text-center">
                            <p class="text-gray-500">This form has no sections yet.</p>
                            <button wire:click="addSection" class="mt-3 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">+ Add section</button>
                        </div>
                    @endforelse

                    <button wire:click="addSection" class="w-full py-2 rounded-lg border-2 border-dashed border-gray-300 text-sm text-gray-500 hover:border-indigo-400 hover:text-indigo-600">
                        + Add section
                    </button>
                </div>

                {{-- Palette --}}
                <div class="w-64 shrink-0 bg-white rounded-lg shadow p-4 lg:sticky lg:top-4 max-h-[calc(100vh-2rem)] overflow-y-auto">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Add fields</h3>
                    <div data-sortable="palette" class="grid grid-cols-2 gap-2">
                        @foreach ($fieldTypes as $type => $meta)
                            @php
                                $icons = [
                                    'text' => 'Aa', 'textarea' => '¶', 'number' => '#',
                                    'email' => '@', 'phone' => '☎', 'url' => '🔗',
                                    'date' => '📅', 'time' => '◷', 'dropdown' => '▾',
                                    'radio' => '◉', 'checkbox' => '☑', 'file' => '📎',
                                    'rating' => '★', 'section' => '▬', 'paragraph' => '…',
                                ];
                                $icon = $icons[$type] ?? '•';
                            @endphp
                            <div
                                data-type="{{ $type }}"
                                wire:key="palette-{{ $type }}"
                                wire:click="addField('{{ $type }}')"
                                class="palette-item flex flex-col items-center gap-1.5 px-3 py-3 rounded-lg border border-gray-200 bg-white text-xs text-gray-600 cursor-grab hover:border-indigo-400 hover:bg-indigo-50 hover:shadow-sm active:cursor-grabbing transition-all"
                                title="Click to add, or drag into the form"
                            >
                                <span class="text-lg leading-none">{{ $icon }}</span>
                                <span class="font-medium">{{ $meta['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Property editor (below both) --}}
            <div class="mt-4 bg-white rounded-lg shadow">
                @if ($selectedField)
                        @php $f = $selectedField; @endphp
                        <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-indigo-100 text-indigo-700 text-sm font-semibold">{{ strtoupper(substr($f['type'], 0, 2)) }}</span>
                                <h3 class="text-sm font-semibold text-gray-800">{{ \App\Services\Schema\FieldTypeRegistry::label($f['type']) }}</h3>
                            </div>
                            <button wire:click="selectField(null)" class="text-gray-400 hover:text-gray-600 text-xs">✕</button>
                        </div>

                        <div class="p-5 space-y-4">
                            {{-- General --}}
                            <div class="rounded-lg border border-gray-200 overflow-hidden">
                                <div class="bg-gray-50 px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">General</div>
                                <div class="px-4 py-3 space-y-3">
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Label</label>
                                        <input type="text" wire:model.debounce.300ms="selectedField.label" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Full Name">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Key</label>
                                        <input type="text" wire:model.debounce.300ms="selectedField.key" class="mt-1 w-full rounded-md border-gray-300 text-sm font-mono shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Placeholder</label>
                                        <input type="text" wire:model.debounce.300ms="selectedField.placeholder" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Help text</label>
                                        <input type="text" wire:model.debounce.300ms="selectedField.help_text" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Default value</label>
                                        <input type="text" wire:model.debounce.300ms="selectedField.default" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div class="flex items-center justify-between py-1">
                                        <span class="text-xs font-medium text-gray-500">Required</span>
                                        <label class="relative inline-flex h-5 w-9 cursor-pointer">
                                            <input type="checkbox" wire:model="selectedField.required" class="peer sr-only">
                                            <span class="absolute inset-0 rounded-full bg-gray-300 transition-colors peer-checked:bg-indigo-600"></span>
                                            <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Options --}}
                            @if (\App\Services\Schema\FieldTypeRegistry::hasOptions($f['type']))
                                <div class="rounded-lg border border-gray-200 overflow-hidden">
                                    <div class="bg-gray-50 px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Options</div>
                                    <div class="px-4 py-3">
                                        <textarea wire:model="optionsText" rows="5" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono" placeholder="One per line, Label | value"></textarea>
                                        <p class="text-[10px] text-gray-400 mt-1">One per line. Use "Label | value" for different display/value.</p>
                                    </div>
                                </div>
                            @endif

                            {{-- Validation --}}
                            <div class="rounded-lg border border-gray-200 overflow-hidden">
                                <div class="bg-gray-50 px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Validation</div>
                                <div class="px-4 py-3 grid grid-cols-2 gap-3">
                                    @if (in_array($f['type'], ['number', 'date']))
                                        <div><label class="text-xs text-gray-500">Min</label><input type="text" wire:model.debounce.400ms="selectedField.validation.min" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                        <div><label class="text-xs text-gray-500">Max</label><input type="text" wire:model.debounce.400ms="selectedField.validation.max" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                    @endif

                                    @if (in_array($f['type'], ['text', 'textarea', 'phone', 'url']))
                                        <div><label class="text-xs text-gray-500">Min length</label><input type="number" wire:model.debounce.400ms="selectedField.validation.min_length" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                        <div><label class="text-xs text-gray-500">Max length</label><input type="number" wire:model.debounce.400ms="selectedField.validation.max_length" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                    @endif

                                    @if (in_array($f['type'], ['text', 'phone']))
                                        <div class="col-span-2"><label class="text-xs text-gray-500">Regex pattern</label><input type="text" wire:model.debounce.400ms="selectedField.validation.pattern" placeholder="/^\d{10}$/" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                    @endif

                                    @if ($f['type'] === 'number')
                                        <div class="col-span-2"><label class="text-xs text-gray-500">Step</label><input type="text" wire:model.debounce.400ms="selectedField.validation.step" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                    @endif

                                    @if ($f['type'] === 'checkbox')
                                        <div><label class="text-xs text-gray-500">Min selections</label><input type="number" wire:model.debounce.400ms="selectedField.validation.min_selections" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                        <div><label class="text-xs text-gray-500">Max selections</label><input type="number" wire:model.debounce.400ms="selectedField.validation.max_selections" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                    @endif

                                    @if ($f['type'] === 'file')
                                        <div class="col-span-2"><label class="text-xs text-gray-500">Allowed types</label><input type="text" wire:model.debounce.400ms="mimesText" placeholder="pdf,jpg,png" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                        <div class="col-span-2"><label class="text-xs text-gray-500">Max file size (KB)</label><input type="number" wire:model.debounce.400ms="selectedField.validation.max_size" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                    @endif

                                    @if (! in_array($f['type'], ['number', 'date', 'text', 'textarea', 'phone', 'url', 'checkbox', 'file']))
                                        <p class="col-span-2 text-xs text-gray-400">No validation options for this field type.</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Conditional logic --}}
                            @if ($f['type'] !== 'section' && $f['type'] !== 'paragraph')
                                <div class="rounded-lg border border-gray-200 overflow-hidden">
                                    <div class="bg-gray-50 px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Conditional visibility</div>
                                    <div class="px-4 py-3">
                                        @forelse ($f['conditions'] ?? [] as $ci => $condition)
                                            <div class="flex items-center gap-1.5 mb-2 text-xs bg-gray-50 rounded-md px-3 py-2">
                                                <span class="text-gray-400 shrink-0">if</span>
                                                <select wire:model="selectedField.conditions.{{ $ci }}.field" class="flex-1 min-w-0 rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Select field…</option>
                                                    @foreach ($this->conditionTargets() as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <select wire:model="selectedField.conditions.{{ $ci }}.operator" class="w-12 rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="equals">=</option>
                                                    <option value="not_equals">≠</option>
                                                    <option value="contains">has</option>
                                                    <option value="empty">is empty</option>
                                                    <option value="not_empty">not empty</option>
                                                </select>
                                                <input type="text" wire:model.debounce.300ms="selectedField.conditions.{{ $ci }}.value" placeholder="value" class="flex-1 min-w-0 rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <button wire:click="removeCondition({{ $ci }})" class="text-red-400 hover:text-red-600 shrink-0">✕</button>
                                            </div>
                                        @empty
                                            <p class="text-xs text-gray-400">No conditions — field is always visible.</p>
                                        @endforelse
                                        <button wire:click="addCondition" class="mt-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium">+ Add condition</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-64 text-center px-6">
                            <div class="text-3xl mb-3 text-gray-300">⚙️</div>
                            <p class="text-sm text-gray-400">Select a field on the canvas to edit its settings.</p>
                        </div>
                    @endif
                </div>
        </div>
    </div>

    {{-- JSON editor modal --}}
    @if ($showJson)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="toggleJson">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl p-5" wire:key="json-modal">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800">JSON schema (single source of truth)</h3>
                    <button wire:click="toggleJson" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <textarea wire:model="jsonText" rows="20" class="w-full rounded-md border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <div class="mt-3 flex items-center justify-end gap-2">
                    <button wire:click="toggleJson" class="px-3 py-2 rounded-md border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button wire:click="applyJson" class="px-4 py-2 rounded-md bg-indigo-600 text-sm text-white hover:bg-indigo-500">Validate & Apply</button>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            window.__formSortables = [];

            const makeSortable = (el, opts) => {
                if (el.dataset.initialized === '1') return;
                el.dataset.initialized = '1';
                window.__formSortables.push(new Sortable(el, opts));
            };

            const wireCall = (el, method, ...args) => {
                const id = el.closest('[wire\\:id]')?.getAttribute('wire:id');
                if (id) Livewire.find(id).call(method, ...args);
            };

            const initFormSortable = () => {
                document.querySelectorAll('[data-sortable="palette"]').forEach((el) => {
                    makeSortable(el, {
                        group: { name: 'formFields', pull: 'clone', put: false },
                        sort: false,
                        animation: 150,
                        onEnd(evt) {
                            if (evt.item.dataset.type && evt.pullMode === 'clone') {
                                evt.item.remove();
                            }
                        },
                    });
                });

                document.querySelectorAll('[data-sortable="fields"]').forEach((el) => {
                    const section = Number(el.dataset.section);
                    makeSortable(el, {
                        group: 'formFields',
                        animation: 150,
                        handle: '[data-handle]',
                        onAdd(evt) {
                            const type = evt.item.dataset.type;
                            if (type) {
                                evt.item.remove();
                                wireCall(el, 'addField', type, section);
                            }
                        },
                        onEnd(evt) {
                            if (!evt.item.dataset.type) {
                                wireCall(el, 'reorderFields', section, evt.oldIndex, evt.newIndex);
                            }
                        },
                    });
                });

                document.querySelectorAll('[data-sortable="sections"]').forEach((el) => {
                    makeSortable(el, {
                        animation: 150,
                        handle: '[data-handle]',
                        onEnd(evt) {
                            wireCall(el, 'reorderSections', evt.oldIndex, evt.newIndex);
                        },
                    });
                });
            };

            initFormSortable();

            Livewire.hook('morph.added', () => initFormSortable());
            Livewire.hook('morph.updated', () => {
                initFormSortable();
                window.__formSortables.forEach((s) => { try { s.refresh(); } catch (e) {} });
            });
        });
    </script>
</div>
