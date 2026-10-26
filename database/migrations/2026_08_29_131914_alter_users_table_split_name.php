<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // 성과 이름 컬럼 추가
            $table->string('last_name')->after('id');
            $table->string('first_name')->after('last_name');
            // 기존 name 컬럼 삭제
            $table->dropColumn('name');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->dropColumn(['last_name', 'first_name']);
        });
    }
};