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
        Schema::create('ssdb_products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->index();
            $table->string('description');
            $table->string('unit')->nullable();
            $table->string('category')->nullable()->index();
            $table->decimal('rate', 12, 4)->default(0);
            $table->string('grade')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssdb_products');
    }
};
