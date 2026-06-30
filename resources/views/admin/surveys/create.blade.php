@extends('layouts.admin')

@section('title', 'New survey')

@section('content')
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.surveys.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
        <h1 class="text-xl font-semibold">New survey</h1>
    </div>

    @include('admin.surveys._form')
@endsection
