@extends('layouts.survey')

@section('title', 'Survey closed')

@section('content')
    <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
            <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        </div>
        <h1 class="mt-4 text-xl font-semibold text-gray-900">This survey is closed</h1>
        <p class="mt-2 text-gray-600">
            <span class="font-medium">{{ $survey->title }}</span> is not currently accepting responses.
        </p>
    </div>
@endsection
