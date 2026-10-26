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
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // DB 내부용 고유번호 (이건 남겨두는 것이 좋습니다)
            $table->string('category')->nullable();       // 카테고리
            $table->string('product_id')->nullable();     // 상품 코드
            $table->string('product_name');               // 상품명 (기존 name)
            $table->text('product_detail')->nullable();   // 상품 설명 (기존 description)
            $table->string('detail_url')->nullable();     // 상세페이지 URL
            $table->integer('price');                     // 가격
            $table->integer('cv')->nullable();            // CV (포인트/볼륨 등)
            $table->integer('pv')->nullable();            // PV (포인트/볼륨 등)
            $table->string('img_url')->nullable();        // 이미지 주소 (기존 image_url)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};