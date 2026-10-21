<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
Schema::create('deposit_transactions', function (Blueprint $table) {
$table->id(); $table->foreignId('borrow_record_id'); $table->foreign('borrow_record_id', 'deposit_transactions_borrow_record_id_fk')->references('id')->on('borrow_records')->restrictOnDelete(); $table->enum('type',['hold','refund','deduct','adjust_up','adjust_down']); $table->foreignId('adjusts_id')->nullable()->unique(); $table->foreign('adjusts_id', 'deposit_transactions_adjusts_id_fk')->references('id')->on('deposit_transactions')->restrictOnDelete(); $table->unsignedInteger('amount'); $table->string('reason')->nullable(); $table->foreignId('processed_by'); $table->foreign('processed_by', 'deposit_transactions_processed_by_fk')->references('id')->on('users')->restrictOnDelete(); $table->dateTime('processed_at'); $table->timestamps();
});
 }
 public function down(): void { Schema::dropIfExists('deposit_transactions'); }
};
