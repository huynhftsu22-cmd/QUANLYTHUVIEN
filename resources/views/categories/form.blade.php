@extends('layouts.app')
@section('title', $category->exists ? 'Sửa thể loại' : 'Thêm thể loại')
@section('content')
<div class="card p-4">
    <h1 class="h3">{{ $category->exists ? 'Sửa thể loại' : 'Thêm thể loại' }}</h1>
    <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">
        @csrf
        @if($category->exists) @method('PUT') @endif
        <div class="mb-3">
            <label class="form-label">Tên thể loại <span class="text-danger">*</span></label>
            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $category->name) }}" maxlength="255" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="5">{{ old('description', $category->description) }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-primary">Lưu</button>
        <a class="btn btn-outline-secondary" href="{{ route('categories.index') }}">Hủy</a>
    </form>
</div>
@endsection
