<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_code')->unique();
            $table->foreignId('drive_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('student_name');
            $table->string('upload_type')->default('single_file');
            $table->unsignedInteger('file_count')->default(1);
            $table->unsignedBigInteger('total_size_bytes')->default(0);
            $table->string('gdrive_target_id')->nullable();
            $table->text('gdrive_web_view_link')->nullable();
            $table->boolean('is_late')->default(false);
            $table->string('ip_address')->nullable();
            $table->dateTime('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
