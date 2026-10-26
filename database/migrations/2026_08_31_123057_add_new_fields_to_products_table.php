<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // 컬럼이 존재하지 않을 때만 안전하게 추가합니다.
            if (!Schema::hasColumn('products', 'category')) {
                $table->string('category')->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('products', 'product_category')) {
                $table->string('product_category')->nullable()->after('category');
            }
            if (!Schema::hasColumn('products', 'description_title')) {
                $table->string('description_title')->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('products', 'cv')) {
                $table->integer('cv')->default(0)->after('price');
            }
            if (!Schema::hasColumn('products', 'pv')) {
                $table->integer('pv')->default(0)->after('cv');
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = ['category', 'product_category', 'description_title', 'cv', 'pv'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};