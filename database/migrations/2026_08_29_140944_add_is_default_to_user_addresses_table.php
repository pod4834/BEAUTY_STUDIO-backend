<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // 기본 배송지 여부 체크용 (기본값은 false)
            $table->boolean('is_default')->default(false)->after('company');
        });
    }

    public function down()
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};