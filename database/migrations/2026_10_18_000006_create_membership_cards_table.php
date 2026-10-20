<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
Schema::create('membership_cards', function (Blueprint $table) {
$table->id(); $table->foreignId('user_id')->constrained()->restrictOnDelete()->index(); $table->string('card_code',20)->unique(); $table->unsignedTinyInteger('months'); $table->unsignedInteger('fee_amount'); $table->date('issued_at'); $table->date('expires_at'); $table->enum('status',['active','suspended','cancelled'])->default('active'); $table->foreignId('issued_by')->constrained('users')->restrictOnDelete(); $table->timestamps();
});
 }
 public function down(): void { Schema::dropIfExists('membership_cards'); }
};
