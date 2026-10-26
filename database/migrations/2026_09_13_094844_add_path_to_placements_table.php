<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            // 경로를 저장할 path 컬럼 추가 (LIKE 검색을 위해 인덱스 추가)
            // ex) m/a1/a2
            $table->string('path', 500)->nullable()->after('sponsor_id');
            
            // path 컬럼에 인덱스를 걸어 검색 속도를 극대화합니다.
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->dropIndex(['path']);
            $table->dropColumn('path');
        });
    }
};