@extends('layouts.survey')

@section('title', 'Already answered')

@section('content')
    <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <h1 class="mt-4 text-xl font-semibold text-gray-900">You've already answered this</h1>
        <p class="mt-2 text-gray-600">
            Our records show you have already submitted a response to
            <span class="font-medium">{{ $survey->title }}</span>. Each person can answer only once.
        </p>
    </div>
@endsection
