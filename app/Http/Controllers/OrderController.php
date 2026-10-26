<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderReceived; 

class OrderController extends Controller 
{
    // 1. 내 주문 내역 불러오기 (마이페이지용)
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)->orderBy('created_at', 'desc')->get();
        
        $orderData = $orders->map(function ($order) {
            $orderArray = $order->toArray(); 
            
            $items = DB::table('order_items')->where('order_id', $order->id)->get();
            
            foreach ($items as $item) {
                $product = DB::table('products')->where('id', $item->product_id)->first();
                if ($product) {
                    $item->img_url = $product->image ?? ($product->img_url ?? null);
                } else {
                    $item->img_url = null;
                }
            }
            
            $orderArray['items'] = $items;
            return $orderArray;
        });

        return response()->json($orderData);
    }

    // 2. 주문 저장 및 결제 처리
    public function store(Request $request)
    {
        $request->validate([
            'cart' => 'required|array',
            'total_amount' => 'required|numeric'
        ]);

        $user = $request->user();

        try {
            DB::beginTransaction();

            $order = new Order();
            $order->user_id = $user->id;

            // 💡 [핵심 추가] YYYYMMDD-XXX 형식의 주문번호 자동 생성 로직
            $today = now()->format('Ymd'); // 오늘 날짜 (예: 20260902)
            
            // 오늘 생성된 주문 중 가장 마지막 주문을 가져옵니다.
            $lastOrder = Order::whereDate('created_at', now()->toDateString())->orderBy('id', 'desc')->first();
            
            if ($lastOrder && $lastOrder->order_number && str_starts_with($lastOrder->order_number, $today)) {
                // 마지막 주문번호에서 하이픈(-) 뒤의 3자리 숫자를 뽑아 +1을 해줍니다.
                $lastSequence = (int) explode('-', $lastOrder->order_number)[1];
                $newSequence = $lastSequence + 1;
            } else {
                // 오늘 첫 주문일 경우 1번부터 시작합니다.
                $newSequence = 1;
            }
            
            // YYYYMMDD-XXX 형태로 조립하여 저장합니다. (예: 20260902-001)
            $order->order_number = $today . '-' . str_pad($newSequence, 3, '0', STR_PAD_LEFT);
            // ---------------------------------------------------------

            $order->total_amount = $request->total_amount;

            $total_cv = 0;
            $total_pv = 0;
            foreach ($request->cart as $item) {
                $qty = $item['quantity'] ?? 1;
                $total_cv += ($item['cv'] ?? 0) * $qty;
                $total_pv += ($item['pv'] ?? 0) * $qty;
            }
            $order->total_cv = $total_cv;
            $order->total_pv = $total_pv;

            $order->payment_method = $request->payment_method ?? 'bank_transfer';

            if ($order->payment_method === 'bank_transfer') {
                $order->status = 'pending'; 
            } else {
                $order->status = 'completed';
            }
            $order->save();

            // 상세 주문 상품 저장
            foreach ($request->cart as $item) {
                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'product_id' => $item['id'] ?? $item['product_id'],
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'] ?? 1,
                ]);
            }

            // DB 장바구니 비우기
            DB::table('carts')->where('user_id', $user->id)->delete();

            DB::commit();

            // 주문 DB 저장이 완료된 후 고객에게 알림 메일 발송
            $mailError = null;
            try {
                Mail::to($user->email)->send(new OrderReceived($order, $user));
            } catch (\Exception $e) {
                Log::error('Order Mail Send Error: ' . $e->getMessage());
                $mailError = $e->getMessage();
            }

            if ($order->payment_method === 'bank_transfer') {
                $message = "ご注文ありがとうございます！\n\n【お振込先口座】\n〇〇銀行 〇〇支店\n普通 1234567\n株式会社CELLVIA\n\n※ご入金確認後に商品の発送準備を進めさせていただきます。";
            } else {
                $message = "ご注文（決済）が完了いたしました！";
            }

            // 메일 전송 실패 시 에러 덧붙이기
            if ($mailError) {
                $message .= "\n\n⚠️ 【注意】メール送信に失敗しました: " . $mailError;
            }

            return response()->json([
                'message' => $message,
                'order_number' => $order->order_number, // 💡 프론트엔드로 새 주문번호 전달
                'order_id' => $order->id
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Error: ' . $e->getMessage());

            return response()->json([
                'message' => '注文処理中にサーバーエラーが発生しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}