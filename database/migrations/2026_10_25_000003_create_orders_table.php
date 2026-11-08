<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('contact_platform');
            $table->string('contact_handle')->nullable();
            $table->string('channel_url')->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->string('platform');
            $table->string('has_design');
            $table->string('design_link')->nullable();
            $table->text('description');
            $table->string('budget')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status')->default('new')->index();
            $table->decimal('quoted_price', 12, 2)->nullable();
            $table->string('quoted_currency', 3)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
