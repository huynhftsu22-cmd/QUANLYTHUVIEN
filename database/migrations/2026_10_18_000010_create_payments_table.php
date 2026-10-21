<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
Schema::create('payments', function (Blueprint $table) {
$table->id(); $table->foreignId('user_id'); $table->foreign('user_id', 'payments_user_id_fk')->references('id')->on('users')->restrictOnDelete(); $table->foreignId('borrow_record_id')->nullable(); $table->foreign('borrow_record_id', 'payments_borrow_record_id_fk')->references('id')->on('borrow_records')->restrictOnDelete(); $table->foreignId('membership_card_id')->nullable(); $table->foreign('membership_card_id', 'payments_membership_card_id_fk')->references('id')->on('membership_cards')->restrictOnDelete(); $table->enum('type',['rental_fee','card_fee','late_fine','damage_fee'])->index(); $table->unsignedInteger('amount'); $table->enum('method',['cash','transfer','deposit'])->default('cash'); $table->enum('status',['paid','cancelled'])->default('paid')->index(); $table->dateTime('paid_at'); $table->foreignId('received_by'); $table->foreign('received_by', 'payments_received_by_fk')->references('id')->on('users')->restrictOnDelete(); $table->string('reference',100)->nullable(); $table->timestamps();
});
 }
 public function down(): void { Schema::dropIfExists('payments'); }
};
