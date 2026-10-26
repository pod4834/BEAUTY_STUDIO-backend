<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // 제목
            $table->text('description'); // 설명문
            $table->text('before_image'); // 비포 이미지 URL
            $table->text('after_image'); // 애프터 이미지 URL
            $table->json('product_ids')->nullable(); // 사용 제품 ID 배열 (JSON 저장)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('galleries');
    }
};