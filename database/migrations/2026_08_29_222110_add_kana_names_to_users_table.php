<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // first_name 뒤에 카타카나 성/이름 필드를 추가합니다. (이미 존재하면 에러가 나지 않도록 체크)
            if (!Schema::hasColumn('users', 'last_name_kana')) {
                $table->string('last_name_kana')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'first_name_kana')) {
                $table->string('first_name_kana')->nullable()->after('last_name_kana');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_name_kana', 'first_name_kana']);
        });
    }
};