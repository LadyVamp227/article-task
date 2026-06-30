@extends('layouts.survey')

@section('title', 'Thank you')

@section('content')
    <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
            <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>
        <h1 class="mt-4 text-xl font-semibold text-gray-900">Thank you!</h1>
        <p class="mt-2 text-gray-600">
            Your response to <span class="font-medium">{{ $survey->title }}</span> has been recorded.
        </p>
        <p class="mt-1 text-sm text-gray-400">You can only answer this survey once.</p>
    </div>
@endsection
