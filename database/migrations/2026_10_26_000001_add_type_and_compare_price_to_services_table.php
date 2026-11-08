<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // package = paket satuan, bundle = gabungan beberapa paket dengan harga normal yang dicoret.
            $table->string('type')->default('package')->after('slug')->index();
            $table->unsignedBigInteger('compare_price_idr')->nullable()->after('price_usd');
            $table->decimal('compare_price_usd', 10, 2)->nullable()->after('compare_price_idr');
            // Bundle tidak punya kategori.
            $table->string('category')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'compare_price_idr', 'compare_price_usd']);
            $table->string('category')->nullable(false)->change();
        });
    }
};
