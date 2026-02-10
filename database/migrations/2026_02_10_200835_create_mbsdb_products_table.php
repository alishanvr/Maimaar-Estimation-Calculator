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
        Schema::create('mbsdb_products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->index();
            $table->string('description');
            $table->string('unit')->nullable();
            $table->string('category')->nullable()->index();
            $table->decimal('rate', 12, 4)->default(0);
            $table->string('rate_type')->default('kg');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mbsdb_products');
    }
};
