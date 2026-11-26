<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('google_email')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('root_folder_id')->nullable();
            $table->unsignedBigInteger('storage_used_bytes')->default(0);
            $table->unsignedBigInteger('storage_limit_bytes')->default(0);
            $table->integer('priority')->default(1);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_full')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_accounts');
    }
};
