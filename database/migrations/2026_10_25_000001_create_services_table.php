<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->string('summary', 255);
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->unsignedBigInteger('price_idr');
            $table->decimal('price_usd', 10, 2);
            $table->boolean('price_is_starting')->default(false);
            $table->string('turnaround')->nullable();
            $table->unsignedTinyInteger('revisions')->default(2);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
