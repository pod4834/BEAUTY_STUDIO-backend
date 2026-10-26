<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('user_addresses', 'last_name_kana')) {
                $table->string('last_name_kana')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('user_addresses', 'first_name_kana')) {
                $table->string('first_name_kana')->nullable()->after('last_name_kana');
            }
        });
    }

    public function down()
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn(['last_name_kana', 'first_name_kana']);
        });
    }
};