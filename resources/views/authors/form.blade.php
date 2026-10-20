@extends('layouts.app')
@section('title', $author->exists ? 'Sửa tác giả' : 'Thêm tác giả')
@section('content')
<div class="card p-4">
    <h1 class="h3">{{ $author->exists ? 'Sửa tác giả' : 'Thêm tác giả' }}</h1>
    <form method="POST" action="{{ $author->exists ? route('authors.update', $author) : route('authors.store') }}">
        @csrf
        @if($author->exists) @method('PUT') @endif
        <div class="mb-3">
            <label class="form-label">Tên tác giả <span class="text-danger">*</span></label>
            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $author->name) }}" maxlength="255" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Tiểu sử</label>
            <textarea class="form-control @error('biography') is-invalid @enderror" name="biography" rows="5">{{ old('biography', $author->biography) }}</textarea>
            @error('biography')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-primary">Lưu</button>
        <a class="btn btn-outline-secondary" href="{{ route('authors.index') }}">Hủy</a>
    </form>
</div>
@endsection
