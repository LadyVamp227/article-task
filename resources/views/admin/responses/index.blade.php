@extends('layouts.admin')

@section('title', 'Responses')

@php
    $typeLabels = [
        'textarea' => 'Long text',
        'single_choice' => 'Single choice',
        'multiple_choice' => 'Multiple choice',
    ];
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.surveys.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
            <div>
                <h1 class="text-xl font-semibold">Responses</h1>
                <p class="text-sm text-gray-500">{{ $survey->title }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700">
                {{ $total }} {{ Str::plural('response', $total) }}
            </span>
        </div>
    </div>

    @if ($total === 0)
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-12 text-center text-gray-500">
            No responses yet. Share the survey link to start collecting answers.
        </div>
    @else
        {{-- Per-question summary --}}
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Summary</h2>
        <div class="mb-8 space-y-4">
            @foreach ($survey->questions as $question)
                @php $s = $summary[$question->id] ?? null; @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-start justify-between gap-4">
                        <h3 class="font-medium text-gray-900">{{ $loop->iteration }}. {{ $question->title }}</h3>
                        <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                            {{ $typeLabels[$question->type] ?? $question->type }}
                        </span>
                    </div>

                    @if (in_array($question->type, ['single_choice', 'multiple_choice']))
                        <div class="space-y-2">
                            @forelse ($s['options'] as $opt)
                                <div>
                                    <div class="mb-0.5 flex justify-between text-sm">
                                        <span class="text-gray-700">{{ $opt['title'] }}</span>
                                        <span class="text-gray-500">{{ $opt['count'] }} ({{ $opt['percent'] }}%)</span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full bg-indigo-500" style="width: {{ $opt['percent'] }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">No options.</p>
                            @endforelse
                        </div>
                    @else
                        <p class="text-sm text-gray-500">
                            {{ $s['answered'] }} text {{ Str::plural('response', $s['answered']) }} —
                            open an individual response below to read them.
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Individual responses --}}
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Individual responses</h2>
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3 text-center">Answers</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($responses as $response)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">#{{ $response->id }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $response->submitted_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $response->answers_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.surveys.responses.show', [$survey, $response]) }}"
                                   class="font-medium text-indigo-600 hover:text-indigo-500">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $responses->links() }}
        </div>
    @endif
@endsection
