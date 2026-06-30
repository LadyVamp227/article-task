@extends('layouts.admin')

@section('title', 'Edit survey')

@section('content')
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.surveys.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
        <h1 class="text-xl font-semibold">Edit: {{ $survey->title }}</h1>
    </div>

    {{-- Shareable private link --}}
    <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4"
         x-data="{ copied: false, copy() { navigator.clipboard.writeText($refs.link.value); this.copied = true; setTimeout(() => this.copied = false, 1500); } }">
        <div class="mb-1 text-sm font-medium text-indigo-900">Private share link</div>
        <p class="mb-3 text-xs text-indigo-700">
            Only people with this link can open the survey. It is unguessable — keep it private.
        </p>
        <div class="flex items-center gap-2">
            <input x-ref="link" type="text" readonly value="{{ $survey->publicUrl() }}"
                   class="block w-full rounded-lg border border-indigo-300 bg-white px-3 py-2 text-sm text-gray-700">
            <button type="button" x-on:click="copy()"
                    class="shrink-0 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                <span x-show="!copied">Copy</span>
                <span x-show="copied" x-cloak>Copied!</span>
            </button>
        </div>
    </div>

    @include('admin.surveys._form')
@endsection
