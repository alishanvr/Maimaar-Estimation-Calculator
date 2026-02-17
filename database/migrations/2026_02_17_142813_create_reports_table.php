<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('estimation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_type', 50);
            $table->string('sheet_name', 50);
            $table->string('filename', 255);
            $table->unsignedInteger('file_size')->nullable();
            $table->timestamps();

            $table->index(['report_type', 'sheet_name']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
