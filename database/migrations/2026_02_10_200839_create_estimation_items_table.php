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
        Schema::create('estimation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimation_id')->constrained()->cascadeOnDelete();
            $table->string('item_code')->nullable();
            $table->string('description');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->decimal('weight_kg', 12, 4)->default(0);
            $table->decimal('rate', 12, 4)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('category')->nullable()->index();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimation_items');
    }
};
