<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
Schema::create('rental_policies', function (Blueprint $table) {
$table->id(); $table->unsignedInteger('daily_rate'); $table->unsignedTinyInteger('student_discount'); $table->unsignedTinyInteger('deposit_rate'); $table->unsignedTinyInteger('member_deposit_rate')->default(0); $table->unsignedInteger('late_fee'); $table->unsignedTinyInteger('damage_rate'); $table->unsignedTinyInteger('lost_rate'); $table->unsignedInteger('lost_admin_fee'); $table->unsignedInteger('card_fee_monthly'); $table->unsignedTinyInteger('max_days'); $table->unsignedTinyInteger('max_books'); $table->unsignedTinyInteger('hold_hours')->default(24); $table->date('effective_at'); $table->boolean('is_active')->default(false); $table->timestamps();
});
 }
 public function down(): void { Schema::dropIfExists('rental_policies'); }
};
