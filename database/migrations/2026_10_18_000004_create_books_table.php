<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
Schema::create('books', function (Blueprint $table) {
$table->id(); $table->string('book_code',30)->unique(); $table->foreignId('author_id')->constrained()->restrictOnDelete(); $table->foreignId('category_id')->constrained()->restrictOnDelete(); $table->string('title')->index(); $table->string('publisher')->nullable(); $table->unsignedSmallInteger('publication_year')->nullable(); $table->unsignedInteger('cover_price'); $table->text('description')->nullable(); $table->string('cover_image')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->timestamps();
});
 }
 public function down(): void { Schema::dropIfExists('books'); }
};
