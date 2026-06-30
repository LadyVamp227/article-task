@extends('layouts.admin')

@section('title', 'Surveys')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Surveys</h1>
        <a href="{{ route('admin.surveys.create') }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            + New survey
        </a>
    </div>

    @php
        $badge = [
            'draft' => 'bg-gray-100 text-gray-700',
            'published' => 'bg-emerald-100 text-emerald-700',
            'closed' => 'bg-amber-100 text-amber-700',
        ];
    @endphp

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Questions</th>
                    <th class="px-4 py-3 text-center">Responses</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($surveys as $survey)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $survey->title }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[$survey->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($survey->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $survey->questions_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.surveys.responses.index', $survey) }}"
                               class="font-medium text-indigo-600 hover:text-indigo-500">{{ $survey->responses_count }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3 text-sm"
                                 x-data="{ copied: false, copy() { navigator.clipboard.writeText('{{ $survey->publicUrl() }}'); this.copied = true; setTimeout(() => this.copied = false, 1500); } }">
                                <button type="button" x-on:click="copy()"
                                        class="font-medium text-gray-500 hover:text-gray-700"
                                        x-bind:title="'{{ $survey->publicUrl() }}'">
                                    <span x-show="!copied">Copy link</span>
                                    <span x-show="copied" x-cloak class="text-emerald-600">Copied!</span>
                                </button>
                                <a href="{{ route('surveys.respond', $survey) }}" target="_blank"
                                   class="text-gray-500 hover:text-gray-700">Open</a>
                                <a href="{{ route('admin.surveys.responses.index', $survey) }}"
                                   class="font-medium text-indigo-600 hover:text-indigo-500">Responses</a>
                                <a href="{{ route('admin.surveys.edit', $survey) }}"
                                   class="font-medium text-indigo-600 hover:text-indigo-500">Edit</a>
                                <form method="POST" action="{{ route('admin.surveys.destroy', $survey) }}"
                                      onsubmit="return confirm('Delete &quot;{{ $survey->title }}&quot; and all its responses?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-600 hover:text-red-500">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">
                            No surveys yet. <a href="{{ route('admin.surveys.create') }}" class="text-indigo-600 hover:underline">Create your first one</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $surveys->links() }}
    </div>
@endsection
