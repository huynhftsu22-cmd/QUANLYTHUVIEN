<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
Schema::create('book_copies', function (Blueprint $table) {
$table->id(); $table->foreignId('book_id')->constrained()->restrictOnDelete(); $table->string('copy_code',40)->unique(); $table->enum('status',['available','reserved','borrowed','damaged','maintenance','lost','retired'])->default('available')->index(); $table->string('condition_note')->nullable(); $table->string('shelf_location',50)->nullable(); $table->timestamps();
});
 }
 public function down(): void { Schema::dropIfExists('book_copies'); }
};
