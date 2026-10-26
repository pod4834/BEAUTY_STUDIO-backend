<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // DB에 존재하는 컬럼 목록을 확인
        $columns = Schema::getColumnListing('products');

        // 변경 대상이 될 수 있는 이미지 관련 컬럼 후보 목록
        $possibleColumns = ['image', 'image_url', 'img_url', 'product_image', 'thumbnail', 'detail_url'];

        foreach ($possibleColumns as $column) {
            // 실제 products 테이블에 해당 컬럼이 존재하는 경우에만 UPDATE 실행
            if (in_array($column, $columns)) {
                DB::statement("
                    UPDATE products 
                    SET {$column} = REGEXP_REPLACE({$column}, '\\.(jpg|jpeg|png)$', '.webp') 
                    WHERE {$column} IS NOT NULL AND {$column} != ''
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};