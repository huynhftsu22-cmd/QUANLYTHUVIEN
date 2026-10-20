<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Author, Category, Book, BookCopy};

class SaySachCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (config('saysach_catalog') as $entry) {
                // A reused code belonging to another title must not be overwritten.
                $book = Book::where('book_code', $entry['code'])->first();
                if ($book && !in_array(mb_strtolower(trim($book->title)), array_map(fn ($title) => mb_strtolower(trim($title)), $entry['aliases']), true)) {
                    $this->command?->warn('Bỏ qua mã đã dùng cho sách khác: '.$entry['code']);
                    continue;
                }
                $author = Author::firstOrCreate(['name' => $entry['author']], ['biography' => null]);
                if (in_array($author->biography, ['Dữ liệu minh họa', 'Tác giả minh họa'], true)) $author->update(['biography' => null]);
                $category = Category::firstOrCreate(['name' => $entry['category']], ['description' => 'Sách thuộc nhóm '.$entry['category'].'.']);
                if (in_array($category->description, ['Danh mục sách mẫu', 'Dữ liệu mẫu'], true)) $category->update(['description' => 'Sách thuộc nhóm '.$entry['category'].'.']);
                $data = collect($entry)->only(['title', 'publisher', 'publication_year', 'cover_price', 'description'])->all();
                $data['author_id'] = $author->id;
                $data['category_id'] = $category->id;
                if ($book) {
                    // Retain uploaded cover, visibility, copy IDs and all borrowing records.
                    $book->update($data);
                } else {
                    $book = Book::create($data + ['book_code' => $entry['code'], 'is_active' => true]);
                    // Physical copies must be entered from the real inventory, never invented.
                }
            }
        });
    }
}
