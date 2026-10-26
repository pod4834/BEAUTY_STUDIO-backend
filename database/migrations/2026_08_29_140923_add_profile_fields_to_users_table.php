<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // 회원정보에 청구지 주소 및 연락처 통합
            $table->string('zip', 10)->nullable()->after('password');
            $table->string('address')->nullable()->after('zip');
            $table->string('phone', 20)->nullable()->after('address');
            $table->string('company')->nullable()->after('phone');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // 롤백 시 추가한 컬럼들 삭제
            $table->dropColumn(['zip', 'address', 'phone', 'company']);
        });
    }
};