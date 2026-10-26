<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Reservation;
use App\Models\EmailTemplate;

class ReservationConfirmed extends Mailable
{
    use Queueable, SerializesModels;
    
    public $reservation;

    public function __construct(Reservation $reservation) 
    { 
        $this->reservation = $reservation; 
    }

    public function build()
    {
        // 💡 관리자가 설정한 '확정(confirmed)' 템플릿을 불러옵니다.
        $template = EmailTemplate::where('template_type', 'confirmed')->first();
        
        // 💡 [핵심 방어막] DB에 템플릿이 없을 경우 뻗지 않고 기본 메일을 발송합니다!
        if (!$template) {
            $fallbackBody = "ご予約が確定いたしました。\n\n名前: " . $this->reservation->name . " 様\n日時: " . $this->reservation->date . " " . $this->reservation->time . "\n\n当日のご来店をスタッフ一同、心よりお待ち申し上げております。";
            
            // 아까 만들어둔 dynamic_text 껍데기를 재활용합니다.
            return $this->subject('【CELLVIA】 ご予約が確定いたしました')->text('emails.dynamic_text', ['content' => $fallbackBody]);
        }

        $body = $template->body;
        
        // 치환용 변수 변환 ({name}, {date} 등을 실제 예약 데이터로 바꿈)
        $body = str_replace('{name}', $this->reservation->name, $body);
        $body = str_replace('{menu}', $this->reservation->menu_id == 1 ? 'Beauty Care' : 'About Business', $body);
        $body = str_replace('{date}', str_replace('-', '/', $this->reservation->date), $body);
        $body = str_replace('{time}', $this->reservation->time, $body);
        $body = str_replace('{phone}', $this->reservation->phone, $body);
        $body = str_replace('{manage_url}', 'http://localhost:5173/?manage=' . $this->reservation->id, $body);

        return $this->subject($template->subject)->text('emails.dynamic_text', ['content' => $body]);
    }
}