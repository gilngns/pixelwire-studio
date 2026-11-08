<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description', 255)->nullable();
            // fixed = harga tetap, percent = persentase dari harga paket, free = gratis.
            $table->string('pricing_type')->default('fixed');
            $table->unsignedBigInteger('price_idr')->default(0);
            $table->decimal('price_usd', 10, 2)->default(0);
            $table->unsignedTinyInteger('percent')->nullable();
            // Lebih dari 1 berarti klien bisa memilih jumlah, misalnya revisi tambahan.
            $table->unsignedTinyInteger('max_quantity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addons');
    }
};
