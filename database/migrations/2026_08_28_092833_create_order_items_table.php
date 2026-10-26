<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade'); // 연결된 주문 번호
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // 구매한 상품 번호
            $table->integer('price'); // 구매 당시 가격
            $table->integer('quantity')->default(1); // 수량 (기본 1개)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};