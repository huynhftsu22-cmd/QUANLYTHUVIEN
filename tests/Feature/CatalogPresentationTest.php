<?php
namespace Tests\Feature;

use App\Models\{Author, Category, Book, BookCopy};
use Database\Seeders\SaySachCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_is_repeatable_and_keeps_inventory_and_visibility(): void
    {
        $this->seed(SaySachCatalogSeeder::class);
        $book = Book::where('book_code', 'S0002')->firstOrFail();
        $book->update(['title' => 'Mắt Biếc', 'publisher' => 'Dữ liệu demo', 'is_active' => false]);
        $copy = BookCopy::create(['book_id' => $book->id, 'copy_code' => 'REAL-001', 'status' => 'reserved', 'shelf_location' => 'B2']);
        $this->seed(SaySachCatalogSeeder::class);
        $this->seed(SaySachCatalogSeeder::class);
        $this->assertSame(17, Book::count());
        $this->assertSame(1, BookCopy::count());
        $this->assertSame('reserved', $copy->fresh()->status);
        $this->assertSame('NXB Trẻ', $book->fresh()->publisher);
        $this->assertFalse((bool) $book->fresh()->is_active);
    }

    public function test_sync_does_not_overwrite_a_reused_code(): void
    {
        $author = Author::create(['name' => 'Tác giả khác']);
        $category = Category::create(['name' => 'Danh mục riêng']);
        $book = Book::create(['book_code' => 'S0002', 'title' => 'Sách riêng', 'author_id' => $author->id, 'category_id' => $category->id, 'cover_price' => 90000]);
        $this->seed(SaySachCatalogSeeder::class);
        $this->assertSame('Sách riêng', $book->fresh()->title);
        $this->assertStringContainsString('default-book-cover.svg', $book->fresh()->cover_url);
    }

    public function test_every_catalog_cover_exists_and_details_have_a_return_link(): void
    {
        $this->seed();
        foreach (Book::all() as $book) {
            $this->assertFileExists(public_path($book->catalog_entry['cover']));
            $this->assertStringNotContainsString('demo', mb_strtolower($book->description));
        }
        $book = Book::where('book_code', 'S0002')->firstOrFail();
        $this->get(route('books.show', ['book' => $book, 'q' => 'Mắt', 'page' => 2]))
            ->assertOk()->assertSee('← Quay lại')->assertDontSee('Quay lại danh sách sách')->assertDontSee('Thông tin ấn bản')->assertSee('edition-S0002')->assertSee('page=2');
        $this->get(route('books.index'))->assertOk()->assertSee('book-cover-frame')->assertDontSee('Bìa minh họa');
    }
}
