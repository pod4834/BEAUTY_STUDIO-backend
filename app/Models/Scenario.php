<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scenario extends Model
{
    use HasFactory;

    // DB 테이블의 어떤 컬럼들을 업데이트할 수 있게 허용할지 설정합니다.
    protected $fillable = [
        'memo',
        // 필요하다면 기존 scenarios 테이블에 있는 다른 컬럼명들도 이곳에 추가해 줍니다. (예: 'title', 'user_id' 등)
    ];
}