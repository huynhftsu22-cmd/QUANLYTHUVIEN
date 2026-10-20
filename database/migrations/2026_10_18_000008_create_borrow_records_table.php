<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
Schema::create('borrow_records', function (Blueprint $table) {
$table->id(); $table->foreignId('user_id')->constrained()->restrictOnDelete(); $table->foreignId('book_copy_id')->constrained('book_copies')->restrictOnDelete(); $table->foreignId('membership_card_id')->nullable()->constrained('membership_cards')->restrictOnDelete(); $table->foreignId('rental_policy_id')->nullable()->constrained('rental_policies')->restrictOnDelete(); $table->date('borrow_date')->nullable(); $table->date('due_date')->nullable(); $table->date('return_date')->nullable(); $table->unsignedTinyInteger('rental_days'); $table->dateTime('requested_at'); $table->unsignedInteger('rental_fee')->default(0); $table->unsignedInteger('discount_amount')->default(0); $table->unsignedInteger('deposit_amount')->default(0); $table->unsignedInteger('cover_price')->nullable(); $table->unsignedInteger('fine_amount')->default(0); $table->unsignedInteger('damage_fee')->default(0); $table->enum('return_condition',['good','damaged','lost'])->nullable(); $table->string('cancel_reason')->nullable(); $table->string('damage_note')->nullable(); $table->foreignId('lent_by')->nullable()->constrained('users')->restrictOnDelete(); $table->enum('status',['pending','borrowing','returned','overdue','cancelled'])->default('pending')->index(); $table->timestamps(); $table->index(['user_id','status']);
});
 }
 public function down(): void { Schema::dropIfExists('borrow_records'); }
};
