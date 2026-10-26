<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    /**
     * 대량 할당(Mass Assignment)이 가능한 속성들입니다.
     * 💡 성/이름, 카나 발음표기, 주소, 연락처 및 기본 배송지 설정 필드가 모두 포함되어 있습니다.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',         // 소유 유저 ID
        'type',            // 주소 타입 (shipping 등)
        'last_name',       // 수령인 성
        'first_name',      // 수령인 이름
        'last_name_kana',  // 수령인 성(카타카나)
        'first_name_kana', // 수령인 이름(카타카나)
        'zip',             // 우편번호
        'address',         // 상세 주소
        'phone',           // 전화번호
        'company',         // 회사명 (선택사항)
        'is_default',      // 기본 배송지 여부
    ];

    /**
     * 데이터 타입 캐스팅을 지정하는 속성들입니다.
     * 💡 DB의 tinyint(0/1) 값을 프론트엔드에서 사용하기 편하게 boolean(true/false)으로 자동 변환합니다.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * 💡 이 주소를 소유하고 있는 유저(User)와의 관계 (N:1)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}