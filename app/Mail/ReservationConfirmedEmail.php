<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Reservation; // 💡 예약 모델 불러오기

class ReservationConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation; // 💡 이메일 템플릿에서 쓸 예약 정보 데이터

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // 💡 메일 제목 설정 및 뷰(템플릿) 연결
        return $this->subject('【CELLVIA】ご予約が確定いたしました') // 메일 제목 (일본어)
                    ->view('emails.reservation_confirmed'); // 연결할 템플릿 파일 (3번에서 만들 파일)
    }
}