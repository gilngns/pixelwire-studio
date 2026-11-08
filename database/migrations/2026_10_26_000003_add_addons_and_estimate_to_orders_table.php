<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Salinan add-on saat order dibuat, supaya riwayat tidak berubah kalau harga add-on diganti.
            $table->json('addons')->nullable()->after('budget');
            $table->decimal('estimated_total', 12, 2)->nullable()->after('addons');
            $table->boolean('estimate_is_starting')->default(false)->after('estimated_total');
            // Diskon studio rekanan, hanya dicatat di admin.
            $table->decimal('partner_discount_percent', 5, 2)->nullable()->after('quoted_currency');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['addons', 'estimated_total', 'estimate_is_starting', 'partner_discount_percent']);
        });
    }
};
