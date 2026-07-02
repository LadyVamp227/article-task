@extends('layouts.survey')

@section('title', $survey->title)

@section('content')
    <header class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $survey->title }}</h1>
        @if ($survey->description)
            <p class="mt-2 text-gray-600">{{ $survey->description }}</p>
        @endif
        <p class="mt-2 text-xs text-gray-400">Answer a question and the next one appears below. Your progress is saved automatically.</p>
    </header>

    <div x-data="surveyWizard(
            @js($questions),
            @js($startId),
            @js($savedAnswers),
            '{{ route('surveys.answer', $survey) }}',
            '{{ route('surveys.thanks', $survey) }}',
            '{{ csrf_token() }}'
         )"
         x-cloak
         class="space-y-4">

        {{-- Revealed questions, one appended below the other. --}}
        <template x-for="qid in visible" x-bind:key="qid">
            <fieldset class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <legend class="px-1 text-base font-medium text-gray-900">
                    <span x-text="q[qid].title"></span>
                    <span x-show="q[qid].required" class="text-red-500" title="Required">*</span>
                </legend>

                <div class="mt-3">
                    {{-- Single choice --}}
                    <template x-if="q[qid].type === 'single_choice'">
                        <div class="space-y-2">
                            <template x-for="o in q[qid].options" x-bind:key="o.id">
                                <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">
                                    <input type="radio" x-bind:name="'q' + qid" x-bind:value="o.id"
                                           x-model.number="answers[qid]" x-on:change="onChange()"
                                           class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span x-text="o.title"></span>
                                </label>
                            </template>
                        </div>
                    </template>

                    {{-- Multiple choice --}}
                    <template x-if="q[qid].type === 'multiple_choice'">
                        <div class="space-y-2">
                            <template x-for="o in q[qid].options" x-bind:key="o.id">
                                <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">
                                    <input type="checkbox" x-bind:value="o.id"
                                           x-model.number="answers[qid]" x-on:change="onChange()"
                                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span x-text="o.title"></span>
                                </label>
                            </template>
                        </div>
                    </template>

                    {{-- Textarea --}}
                    <template x-if="q[qid].type === 'textarea'">
                        <textarea rows="4" x-model="answers[qid]" x-on:change="onChange()"
                                  class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </template>
                </div>
            </fieldset>
        </template>

        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-400" x-show="saving">Saving…</span>
            <button type="button" x-on:click="submit()" x-show="completed"
                    class="ml-auto rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Submit response
            </button>
        </div>
    </div>
@endsection
