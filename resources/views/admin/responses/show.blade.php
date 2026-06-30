@extends('layouts.admin')

@section('title', 'Response #'.$response->id)

@section('content')
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.surveys.responses.index', $survey) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; All responses</a>
        <h1 class="text-xl font-semibold">Response #{{ $response->id }}</h1>
    </div>

    <div class="mb-6 grid gap-3 rounded-xl border border-gray-200 bg-white p-5 text-sm shadow-sm sm:grid-cols-2">
        <div>
            <div class="text-xs uppercase tracking-wide text-gray-400">Survey</div>
            <div class="font-medium text-gray-900">{{ $survey->title }}</div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wide text-gray-400">Submitted</div>
            <div class="text-gray-700">{{ $response->submitted_at?->format('Y-m-d H:i:s') }}</div>
        </div>
    </div>

    <div class="space-y-4">
        @foreach ($survey->questions as $question)
            @php $answers = $answersByQuestion->get($question->id); @endphp
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-2 text-sm font-medium text-gray-900">{{ $loop->iteration }}. {{ $question->title }}</div>

                @if ($answers === null || $answers->isEmpty())
                    <p class="text-sm italic text-gray-400">No answer</p>
                @elseif (in_array($question->type, ['single_choice', 'multiple_choice']))
                    <ul class="list-inside list-disc text-sm text-gray-700">
                        @foreach ($answers as $answer)
                            <li>{{ $answer->option?->label ?? '—' }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="whitespace-pre-line text-sm text-gray-700">{{ $answers->first()->value }}</p>
                @endif
            </div>
        @endforeach
    </div>
@endsection
