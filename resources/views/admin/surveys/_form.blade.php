@php
    $existingQuestions = $survey->exists
        ? $survey->questions->map(fn ($q) => [
            'title' => $q->title,
            'type' => $q->type,
            'is_required' => (bool) $q->is_required,
            'options' => $q->options->map(fn ($o) => [
                'label' => $o->label,
                'value' => $o->value,
            ])->values(),
        ])->values()
        : [];

    $initialQuestions = old('questions', $existingQuestions);
@endphp

<form method="POST"
      action="{{ $survey->exists ? route('admin.surveys.update', $survey) : route('admin.surveys.store') }}"
      class="space-y-8">
    @csrf
    @if ($survey->exists)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            Please fix the errors below.
        </div>
    @endif

    {{-- Survey details --}}
    <section class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-gray-900">Details</h2>

        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">
                Title <span class="text-red-500" title="Required">*</span>
            </label>
            <input id="title" type="text" name="title" value="{{ old('title', $survey->title) }}" required
                   class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea id="description" name="description" rows="3"
                      class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $survey->description) }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select id="status" name="status"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $survey->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="starts_at" class="block text-sm font-medium text-gray-700">Opens at</label>
                <input id="starts_at" type="datetime-local" name="starts_at"
                       value="{{ old('starts_at', $survey->starts_at?->format('Y-m-d\TH:i')) }}"
                       class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('starts_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ends_at" class="block text-sm font-medium text-gray-700">Closes at</label>
                <input id="ends_at" type="datetime-local" name="ends_at"
                       value="{{ old('ends_at', $survey->ends_at?->format('Y-m-d\TH:i')) }}"
                       class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('ends_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- Questions builder (Alpine) --}}
    <section class="space-y-4" x-data="surveyForm(@js($initialQuestions))">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">
                Questions <span class="text-red-500" title="At least one question is required">*</span>
            </h2>
            <button type="button" x-on:click="addQuestion()"
                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                + Add question
            </button>
        </div>

        @error('questions')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror

        <template x-if="questions.length === 0">
            <p class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-8 text-center text-sm text-gray-500">
                No questions yet. Click “Add question” to start.
            </p>
        </template>

        <template x-for="(q, qi) in questions" x-bind:key="qi">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <span class="mt-2 text-sm font-semibold text-gray-400" x-text="'#' + (qi + 1)"></span>
                    <button type="button" x-on:click="removeQuestion(qi)"
                            class="text-sm font-medium text-red-600 hover:text-red-500">Remove</button>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Question title</label>
                        <input type="text" required x-model="q.title"
                               x-bind:name="`questions[${qi}][title]`"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select x-model="q.type" x-on:change="onTypeChange(qi)"
                                x-bind:name="`questions[${qi}][type]`"
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($questionTypes as $type)
                                <option value="{{ $type }}">{{ Str::headline($type) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        {{-- Hidden 0 + checkbox 1 so the value always submits. --}}
                        <input type="hidden" value="0" x-bind:name="`questions[${qi}][is_required]`">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" value="1" x-model="q.is_required"
                                   x-bind:name="`questions[${qi}][is_required]`"
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Required
                        </label>
                    </div>
                </div>

                {{-- Options (only for choice questions) --}}
                <template x-if="isChoice(q.type)">
                    <div class="mt-4 rounded-lg bg-gray-50 p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Options</span>
                            <button type="button" x-on:click="addOption(qi)"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500">+ Add option</button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(o, oi) in q.options" x-bind:key="oi">
                                <div class="flex items-center gap-2">
                                    <input type="text" placeholder="Option label" required x-model="o.label"
                                           x-bind:name="`questions[${qi}][options][${oi}][label]`"
                                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <button type="button" x-on:click="removeOption(qi, oi)"
                                            class="shrink-0 rounded-lg border border-gray-300 px-2 py-2 text-xs text-gray-500 hover:bg-white">✕</button>
                                </div>
                            </template>
                            <template x-if="q.options.length === 0">
                                <p class="text-xs text-gray-400">Add at least one option for this question type.</p>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </section>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.surveys.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</a>
        <button type="submit"
                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            {{ $survey->exists ? 'Save changes' : 'Create survey' }}
        </button>
    </div>
</form>
