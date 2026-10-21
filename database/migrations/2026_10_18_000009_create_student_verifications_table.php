<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
Schema::create('student_verifications', function (Blueprint $table) {
$table->id(); $table->foreignId('user_id'); $table->foreign('user_id', 'student_verifications_user_id_fk')->references('id')->on('users')->restrictOnDelete(); $table->string('school_name'); $table->string('student_code',30); $table->string('evidence_path')->nullable(); $table->enum('status',['pending','approved','rejected'])->default('pending')->index(); $table->foreignId('verified_by')->nullable(); $table->foreign('verified_by', 'student_verifications_verified_by_fk')->references('id')->on('users')->restrictOnDelete(); $table->date('verified_at')->nullable(); $table->date('expires_at')->nullable(); $table->string('reject_reason')->nullable(); $table->timestamps();
});
 }
 public function down(): void { Schema::dropIfExists('student_verifications'); }
};
