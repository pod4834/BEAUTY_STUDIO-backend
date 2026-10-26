<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Reservation;
use App\Models\EmailTemplate;

class ReservationModified extends Mailable
{
    use Queueable, SerializesModels;
    
    public $reservation;

    public function __construct(Reservation $reservation) 
    { 
        $this->reservation = $reservation; 
    }

    public function build()
    {
        // DB에서 'modified(변경)' 템플릿을 찾습니다.
        $template = EmailTemplate::where('template_type', 'modified')->first();
        
        // 💡 [방어막] DB에 템플릿이 없을 경우 기본 메일 발송
        if (!$template) {
            $fallbackBody = "ご予約日時が変更されました。\n\n名前: " . $this->reservation->name . " 様\n変更後の日時: " . $this->reservation->date . " " . $this->reservation->time . "\n\n当日のご来店をスタッフ一同、心よりお待ち申し上げております。";
            
            return $this->subject('【CELLVIA】 ご予約日時変更のお知らせ')->text('emails.dynamic_text', ['content' => $fallbackBody]);
        }

        $body = $template->body;
        
        $body = str_replace('{name}', $this->reservation->name, $body);
        $body = str_replace('{menu}', $this->reservation->menu_id == 1 ? 'Beauty Care' : 'About Business', $body);
        $body = str_replace('{date}', str_replace('-', '/', $this->reservation->date), $body);
        $body = str_replace('{time}', $this->reservation->time, $body);
        $body = str_replace('{phone}', $this->reservation->phone, $body);
        $body = str_replace('{manage_url}', 'http://localhost:5173/?manage=' . $this->reservation->id, $body);

        return $this->subject($template->subject)->text('emails.dynamic_text', ['content' => $body]);
    }
}