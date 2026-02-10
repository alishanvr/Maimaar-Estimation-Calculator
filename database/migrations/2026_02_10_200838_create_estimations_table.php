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
        Schema::create('estimations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('quote_number')->nullable()->index();
            $table->string('revision_no')->nullable();
            $table->string('building_name')->nullable();
            $table->string('building_no')->nullable();
            $table->string('project_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('salesperson_code')->nullable();
            $table->date('estimation_date')->nullable();
            $table->string('status')->default('draft');
            $table->json('input_data')->nullable();
            $table->json('results_data')->nullable();
            $table->decimal('total_weight_mt', 12, 4)->nullable();
            $table->decimal('total_price_aed', 14, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimations');
    }
};
