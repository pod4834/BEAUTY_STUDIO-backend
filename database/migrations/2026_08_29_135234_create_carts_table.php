<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('cart_data')->nullable(); // 💡 장바구니 배열을 통째로 저장
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('carts');
    }
};