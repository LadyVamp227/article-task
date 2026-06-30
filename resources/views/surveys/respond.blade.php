@extends('layouts.survey')

@section('title', $survey->title)

@section('content')
    <header class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $survey->title }}</h1>
        @if ($survey->description)
            <p class="mt-2 text-gray-600">{{ $survey->description }}</p>
        @endif
    </header>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            Please review the highlighted questions below.
        </div>
    @endif

    <form method="POST" action="{{ route('surveys.submit', $survey) }}" class="space-y-8">
        @csrf

        @foreach ($survey->questions as $question)
            @php $field = "answers.$question->id"; @endphp
            <fieldset class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <legend class="px-1 text-base font-medium text-gray-900">
                    {{ $loop->iteration }}. {{ $question->title }}
                    @if ($question->is_required)
                        <span class="text-red-500" title="Required">*</span>
                    @endif
                </legend>

                <div class="mt-3">
                    @switch($question->type)
                        @case('textarea')
                            <textarea name="answers[{{ $question->id }}]" rows="4"
                                      @required($question->is_required)
                                      class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old($field) }}</textarea>
                            @break

                        @case('single_choice')
                            <div class="space-y-2">
                                @foreach ($question->options as $option)
                                    <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                                               @checked(old($field) == $option->id)
                                               @required($question->is_required)
                                               class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>{{ $option->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case('multiple_choice')
                            <div class="space-y-2">
                                @foreach ($question->options as $option)
                                    <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">
                                        <input type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $option->id }}"
                                               @checked(collect(old($field, []))->contains($option->id))
                                               class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>{{ $option->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @break
                    @endswitch
                </div>

                @error($field)
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>
        @endforeach

        <div class="flex items-center justify-end">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Submit response
            </button>
        </div>
    </form>
@endsection
