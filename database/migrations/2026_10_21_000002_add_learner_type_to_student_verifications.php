<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('student_verifications', function (Blueprint $table) {
            // Existing records were submitted through the university-student form.
            $table->string('learner_type',20)->default('university');
        });
    }
    public function down(): void {
        Schema::table('student_verifications', function (Blueprint $table) { $table->dropColumn('learner_type'); });
    }
};
