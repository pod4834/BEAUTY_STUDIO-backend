<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\User;
use App\Models\EmailTemplate;

class OrderShipped extends Mailable
{
    use Queueable, SerializesModels;
    
    public $order;
    public $user;

    public function __construct(Order $order, User $user) 
    { 
        $this->order = $order; 
        $this->user = $user;
    }

    public function build()
    {
        // 💡 DB에서 'shipped(배송 완료)' 템플릿을 찾습니다.
        $template = EmailTemplate::where('template_type', 'shipped')->first();
        
        // 송장번호가 입력되어 있으면 표시하고, 없으면 빈칸으로 처리
        $trackingText = $this->order->tracking_number ? "\n送り状番号: " . $this->order->tracking_number : "";

        // 💡 [방어막] DB에 템플릿이 없을 경우 기본 메일 발송
        if (!$template) {
            $fallbackBody = $this->user->name . " 様\n\nご注文いただいた商品の発送が完了いたしました。\n\n注文番号: #" . $this->order->id . $trackingText . "\n\n商品の到着まで今しばらくお待ちくださいませ。\nご利用誠にありがとうございます。";
            
            return $this->subject('【CELLVIA】 商品発送のお知らせ   ')->text('emails.dynamic_text', ['content' => $fallbackBody]);
        }

        $body = $template->body;
        
        // 치환용 변수 변환
        $body = str_replace('{name}', $this->user->name, $body);
        $body = str_replace('{order_id}', $this->order->id, $body);
        $body = str_replace('{tracking_number}', $this->order->tracking_number ?? '未設定', $body);
        
        return $this->subject($template->subject)->text('emails.dynamic_text', ['content' => $body]);
    }
}