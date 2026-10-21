@php
    $entry = $book->catalog_entry;
    $usesCatalog = $entry && !($book->cover_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($book->cover_image));
@endphp
<span class="book-cover-frame {{ $coverClass ?? 'catalog-cover' }} {{ $usesCatalog ? 'edition-cover edition-'.$entry['code'] : '' }}">
    <img src="{{ $book->cover_url }}" alt="Bìa {{ $book->title }}" loading="lazy">
</span>
