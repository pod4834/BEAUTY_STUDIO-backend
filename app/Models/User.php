<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// 💡 메일 발송 및 URL 생성에 필요한 도구들
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

// 💡 [추가됨 1] Filament 관리자 페이지에서 이름을 표시하기 위한 기능(Contract) 불러오기
use Filament\Models\Contracts\HasName;

// 💡 [추가됨 2] implements HasName 를 붙여서 이 모델이 이름을 제공할 수 있다고 선언합니다.
class User extends Authenticatable implements HasName
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 대량 할당(Mass Assignment)이 가능한 속성들입니다.
     * 💡 성/이름, 카나 발음표기, 청구지 통합 주소 및 연락처 정보가 모두 포함되어 있습니다.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'last_name',       // 성
        'first_name',      // 이름
        'last_name_kana',  // 성(카타카나)
        'first_name_kana', // 이름(카타카나)
        'email',           // 이메일
        'password',        // 비밀번호
        'zip',             // 우편번호
        'address',         // 주소
        'phone',           // 전화번호
        'company',         // 회사명 (선택)
    ];

    /**
     * 배열이나 JSON으로 변환할 때 숨겨야 할 속성들입니다.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 데이터 타입 캐스팅을 지정하는 속성들입니다.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * 💡 유저의 배송지 주소 목록
     * 유저 1명이 여러 개의 배송지(1:N)를 가질 수 있도록 관계를 설정합니다.
     */
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * 💡 유저의 장바구니
     * 유저 1명이 1개의 장바구니(1:1)를 가지도록 관계를 설정합니다.
     */
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    // ---------------------------------------------------------
    // 💡 라라벨 기본 이메일 발송 시스템 가로채기 (텍스트 메일 적용)
    // ---------------------------------------------------------

    /**
     * 1. 회원가입 이메일 발송 가로채기
     */
    public function sendEmailVerificationNotification()
    {
        // 인증 URL 생성
        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );

        // 우리가 직접 만든 텍스트 전용 메일 클래스(VerifyEmailText)를 호출해서 발송합니다.
        Mail::to($this->email)->send(new \App\Mail\VerifyEmailText($this, $url));
    }

    /**
     * 2. 비밀번호 재설정 이메일 발송 가로채기
     */
    public function sendPasswordResetNotification($token)
    {
        // 프론트엔드의 비밀번호 변경 페이지 URL 설정 (React 라우터 주소에 맞게 세팅)
        $url = url('/reset-password?token=' . $token . '&email=' . urlencode($this->email));

        // 우리가 직접 만든 텍스트 전용 메일 클래스(ResetPasswordText)를 호출해서 발송합니다.
        Mail::to($this->email)->send(new \App\Mail\ResetPasswordText($this, $url));
    }

    // ---------------------------------------------------------
    // 💡 [추가됨 3] Filament 관리자 페이지 우측 상단 이름 출력 함수
    // ---------------------------------------------------------
    public function getFilamentName(): string
    {
        // 성과 이름을 합쳐서 관리자 화면 우측 상단에 띄워줍니다. (예: Koo Bonjin)
        return "{$this->last_name} {$this->first_name}";
    }
}