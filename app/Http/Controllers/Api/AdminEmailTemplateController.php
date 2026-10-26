<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class AdminEmailTemplateController extends Controller
{
    // 초기 기본값 세팅
    private $defaults = [
        // --- 📅 예약 관련 메일 ---
        'received' => [
            'subject' => '【CELLVIA】ご予約を承りました',
            'body' => "{name} 様\n\nご予約ありがとうございます。\n以下の内容でご予約を受け付けました。\n\n【ご予約内容】\n▪ コース: {menu}\n▪ 日時: {date} {time}\n▪ お電話番号: {phone}\n\nご予約の確認・変更・キャンセルは以下のリンクよりお願いいたします。\n{manage_url}\n\n※ オンラインでの変更・キャンセルは前日まで可能です。\n\n--------------------------------------------------\nCELLVIA 本店\nTEL: 0120-000-000\n--------------------------------------------------"
        ],
        'confirmed' => [
            'subject' => '【CELLVIA】ご予約が確定いたしました',
            'body' => "{name} 様\n\nご予約ありがとうございます。\nお客様のご予約が正式に確定いたしましたのでお知らせいたします。\n\n【ご予約内容】\n▪ コース: {menu}\n▪ 日時: {date} {time}\n▪ お電話番号: {phone}\n\nご来店をスタッフ一同、心よりお待ち申し上げております。\n\nご予約の確認・変更・キャンセルは以下のリンクよりお願いいたします。\n{manage_url}\n\n--------------------------------------------------\nCELLVIA 本店\nTEL: 0120-000-000\n--------------------------------------------------"
        ],
        'modified' => [
            'subject' => '【CELLVIA】ご予約日時の変更を承りました',
            'body' => "{name} 様\n\nご予約日時の変更を承りました。\n以下の新しい内容でご予約を確定いたしました。\n\n【変更後のご予約内容】\n▪ コース: {menu}\n▪ 日時: {date} {time}\n▪ お電話番号: {phone}\n\nご予約の確認・変更・キャンセルは以下のリンクよりお願いいたします。\n{manage_url}\n\n--------------------------------------------------\nCELLVIA 本店\nTEL: 0120-000-000\n--------------------------------------------------"
        ],
        'cancelled' => [
            'subject' => '【CELLVIA】ご予約のキャンセルを承りました',
            'body' => "{name} 様\n\n以下のご予約につきまして、キャンセル手続きが完了いたしました。\n\n【キャンセルされたご予約内容】\n▪ コース: {menu}\n▪ 日時: {date} {time}\n\nまたのご利用を心よりお待ち申し上げております。\n\n--------------------------------------------------\nCELLVIA 本店\nTEL: 0120-000-000\n--------------------------------------------------"
        ],
        
        // --- 🛒 쇼핑몰(주문) 관련 메일 (추가됨) ---
        'order_received' => [
            'subject' => '【CELLVIA】ご注文ありがとうございます',
            'body' => "{name} 様\n\nこの度はCELLVIAをご利用いただき、誠にありがとうございます。\n以下の内容でご注文を受け付けました。\n\n【ご注文内容】\n▪ 注文番号: #{order_id}\n▪ 決済金額: ¥{total_amount}\n\nご注文の詳細は以下のリンクよりマイページにてご確認いただけます。\n{manage_url}\n\n商品の発送準備が整いましたら、改めてご連絡いたします。\n\n--------------------------------------------------\nCELLVIA\nTEL: 0120-000-000\n--------------------------------------------------"
        ],
        'order_confirmed' => [
            'subject' => '【CELLVIA】ご注文が確定いたしました',
            'body' => "{name} 様\n\nご注文ありがとうございます。\nお客様のご注文が正式に決済され、確定いたしましたのでお知らせいたします。\n\n【ご注文内容】\n▪ 注文番号: #{order_id}\n▪ 決済金額: ¥{total_amount}\n\n現在、発送の準備を進めております。\n発送が完了しましたら、伝票番号を添えて改めてメールにてお知らせいたします。\n\n--------------------------------------------------\nCELLVIA\nTEL: 0120-000-000\n--------------------------------------------------"
        ],
        'order_shipped' => [
            'subject' => '【CELLVIA】商品の発送が完了いたしました',
            'body' => "{name} 様\n\nご注文いただいた商品の発送が完了いたしましたのでお知らせいたします。\n\n【発送情報】\n▪ 注文番号: #{order_id}\n▪ 配送伝票番号（送り状番号）: {tracking_number}\n\n商品の到着まで今しばらくお待ちくださいませ。\nご不明な点がございましたら、お気軽にお問い合わせください。\n\nご注文の詳細はマイページよりご確認いただけます。\n{manage_url}\n\n--------------------------------------------------\nCELLVIA\nTEL: 0120-000-000\n--------------------------------------------------"
        ]
    ];

    public function index()
    {
        // DB에 템플릿이 없으면 기본값으로 자동 생성
        foreach ($this->defaults as $type => $data) {
            EmailTemplate::firstOrCreate(['template_type' => $type], $data);
        }
        return response()->json(EmailTemplate::all());
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'subject' => 'required|string',
            'body' => 'required|string'
        ]);

        $template = EmailTemplate::findOrFail($id);
        $template->update($request->only(['subject', 'body']));

        return response()->json(['message' => 'メールテンプレートを保存しました。']);
    }
}