<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB; // 💡 DB 조회를 위해 추가 필수!

class VerifyEmailText extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $url;

    public function __construct($user, $url)
    {
        $this->user = $user;
        $this->url = $url;
    }

    public function build()
    {
        // 💡 DB에서 템플릿 불러오기 (기둥 이름: template_type)
        $template = DB::table('email_templates')->where('template_type', 'verification')->first();
        
        // 💡 만약 DB에서 삭제되었을 경우를 대비한 기본값(안전장치)
        $subject = $template ? $template->subject : '【CELLVIA】メールアドレスのご確認';
        $body = $template ? $template->body : "{name} 様\n\n以下のURLにアクセスして認証してください。\n{link}";

        // 💡 관리자가 작성한 {name}과 {link} 자리에 실제 유저 이름과 인증 주소를 쏙 넣어줍니다.
        $userName = $this->user->last_name . ' ' . $this->user->first_name;
        $content = str_replace(['{name}', '{link}'], [$userName, $this->url], $body);

        // 💡 정해진 텍스트 템플릿 도화지(dynamic_text)에 완성된 내용을 담아 보냅니다.
        return $this->subject($subject)
                    ->text('emails.dynamic_text')
                    ->with(['content' => $content]);
    }
}