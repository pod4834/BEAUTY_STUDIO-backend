<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 유저가 삭제되면 주소도 삭제
            $table->enum('type', ['billing', 'shipping']); // 청구지인지 배송지인지 구분
            $table->string('last_name');
            $table->string('first_name');
            $table->string('zip', 10); // 우편번호
            $table->string('address'); // 주소
            $table->string('phone', 20); // 전화번호
            $table->string('company')->nullable(); // 회사명 (선택사항)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_addresses');
    }
};