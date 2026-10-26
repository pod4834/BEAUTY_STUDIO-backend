<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\User;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\DB;

class OrderReceived extends Mailable
{
    use Queueable, SerializesModels;
    
    public $order;
    public $user;

    // 주문 정보와 고객 정보를 모두 받습니다.
    public function __construct(Order $order, User $user) 
    { 
        $this->order = $order; 
        $this->user = $user;
    }

    public function build()
    {
        // DB 테이블 조회는 기존과 동일하게 고유 ID를 사용합니다. (외래키 연결)
        $orderItems = DB::table('order_items')->where('order_id', $this->order->id)->get();
        
        $orderDetailsText = "【 ご注文内容 】\n";
        $orderDetailsText .= "--------------------------------------------------\n";
        foreach ($orderItems as $item) {
            $itemTotal = $item->price * $item->quantity;
            $orderDetailsText .= "・ " . $item->product_name . "\n";
            $orderDetailsText .= "   数量: " . $item->quantity . "個 / 小計: ¥" . number_format($itemTotal) . "\n";
        }
        $orderDetailsText .= "--------------------------------------------------\n";
        $orderDetailsText .= "合計金額: ¥" . number_format($this->order->total_amount) . "\n";

        // 💡 [핵심] 고객에게 보여줄 주문번호 설정 (새로 만든 order_number가 없으면 기존 id로 대체)
        $displayOrderNumber = $this->order->order_number ?? $this->order->id;

        // DB에서 'order_received(주문 접수)' 템플릿을 찾습니다.
        $template = EmailTemplate::where('template_type', 'order_received')->first();
        
        if (!$template) {
            // 💡 템플릿이 없을 때 날아가는 기본 메일에도 새 주문번호 적용
            $fallbackBody = $this->user->name . " 様\n\nご注文（注文番号: #" . $displayOrderNumber . "）を確かに承りました。\n\n" . $orderDetailsText . "\n\nマイページよりご注文の詳細をご確認いただけます。\nご利用ありがとうございます。";
            
            return $this->subject('【CELLVIA】 ご注文ありがとうございます')->text('emails.dynamic_text', ['content' => $fallbackBody]);
        }

        $body = $template->body;
        
        // 치환용 변수 변환
        $body = str_replace('{name}', $this->user->name, $body);
        // 💡 관리자 템플릿의 {order_id} 위치에 새 주문번호가 들어가도록 수정
        $body = str_replace('{order_id}', $displayOrderNumber, $body);
        $body = str_replace('{total_amount}', number_format($this->order->total_amount), $body);
        $body = str_replace('{order_details}', $orderDetailsText, $body);
        
        return $this->subject($template->subject)->text('emails.dynamic_text', ['content' => $body]);
    }
}