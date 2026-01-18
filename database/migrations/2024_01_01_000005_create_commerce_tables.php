<?php

declare(strict_types=1);

use App\Core\Database\Migration\Migration;
use App\Core\Database\Migration\Schema;
use App\Core\Database\Migration\Blueprint;

/**
 * Create commerce tables for orders and payments.
 */
class CreateCommerceTables extends Migration
{
    public function up(): void
    {
        // Orders Table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->decimal('total_amount', 10, 2);
            $table->string('status', 20)->default('pending');
            $table->string('payment_method')->nullable()->default(null);
            $table->string('transaction_id')->nullable();
            $table->timestamps();
            $table->unique(['transaction_id']);
            $table->check('status', ['pending', 'paid', 'failed', 'refunded']);
        });

        // Order Items Table
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->references('id')->on('orders')->onDelete('CASCADE');
            $table->foreignId('course_id')->references('id')->on('courses')->onDelete('CASCADE');
            $table->decimal('price_at_purchase', 10, 2);
        });

        // Coupons Table
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->integer('discount_percent');
            $table->datetime('valid_until')->nullable()->default(null);
            $table->integer('usage_limit')->default(0);  // 0 = unlimited
            $table->integer('used_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }

    public function getDescription(): string
    {
        return 'Create commerce tables: orders, order_items, coupons';
    }
}
