<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Book extends Model {
 protected $fillable = ['book_code', 'title', 'author_id', 'category_id', 'publisher', 'publication_year', 'cover_price', 'description', 'cover_image', 'is_active'];
 public function author(): BelongsTo { return $this->belongsTo(Author::class); }
 public function category(): BelongsTo { return $this->belongsTo(Category::class); }
 public function copies(): HasMany { return $this->hasMany(BookCopy::class); }

 public function getCatalogEntryAttribute(): ?array {
  $title = mb_strtolower(trim((string) $this->title));
  foreach (config('saysach_catalog', []) as $entry) {
   foreach ($entry['aliases'] as $alias) {
    if ($title === mb_strtolower(trim($alias))) return $entry;
   }
  }
  return null;
 }
 public function getCoverUrlAttribute(): string {
  if ($this->cover_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->cover_image)) {
   return asset('storage/'.$this->cover_image);
  }
  $entry = $this->catalog_entry;
  return asset($entry ? $entry['cover'] : 'images/default-book-cover.svg');
 }
}
