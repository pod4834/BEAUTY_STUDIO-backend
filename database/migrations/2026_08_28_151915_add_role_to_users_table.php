<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // 💡 password 칸 뒤에 role 칸을 추가하고, 기본값은 'consumer(소비자)'로 설정
            $table->string('role')->default('consumer')->after('password');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role'); // 롤백 시 칸 삭제
        });
    }
};