<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('renamed_filename');
            $table->text('relative_path')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('mime_type')->nullable();
            $table->string('gdrive_file_id')->nullable();
            $table->text('gdrive_web_view_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_items');
    }
};
