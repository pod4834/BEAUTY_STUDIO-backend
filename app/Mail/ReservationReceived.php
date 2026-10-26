<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Reservation;
use App\Models\EmailTemplate;

class ReservationReceived extends Mailable
{
    use Queueable, SerializesModels;
    public $reservation;

    public function __construct(Reservation $reservation) { $this->reservation = $reservation; }

    public function build()
    {
        $template = EmailTemplate::where('template_type', 'received')->first();
        
        // 💡 [핵심 방어막] 만약 DB에 템플릿 세팅이 안 되어있을 경우 에러 방지!
        if (!$template) {
            $fallbackBody = "ご予約を受け付けました。\n名前: " . $this->reservation->name . " 様\n日時: " . $this->reservation->date . " " . $this->reservation->time . "\n\n※管理画面のメールテンプレート設定をご確認ください。";
            return $this->subject('【CELLVIA】 ご予約を受け付けました (임시 안내)')->text('emails.dynamic_text', ['content' => $fallbackBody]);
        }

        $body = $template->body;
        
        // 치환용 변수 변환
        $body = str_replace('{name}', $this->reservation->name, $body);
        $body = str_replace('{menu}', $this->reservation->menu_id == 1 ? 'Beauty Care' : 'About Business', $body);
        $body = str_replace('{date}', str_replace('-', '/', $this->reservation->date), $body);
        $body = str_replace('{time}', $this->reservation->time, $body);
        $body = str_replace('{phone}', $this->reservation->phone, $body);
        $body = str_replace('{manage_url}', 'http://localhost:5173/?manage=' . $this->reservation->id, $body);

        return $this->subject($template->subject)->text('emails.dynamic_text', ['content' => $body]);
    }
}