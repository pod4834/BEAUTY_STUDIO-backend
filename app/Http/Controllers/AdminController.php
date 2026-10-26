<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation; 
use App\Models\User; 
use Illuminate\Support\Facades\Mail; 
use Illuminate\Support\Facades\DB;

// 💡 4가지 메일 클래스를 모두 불러옵니다. (OrderShipped 추가!)
use App\Mail\ReservationConfirmed; 
use App\Mail\ReservationCancelled; 
use App\Mail\ReservationModified; 
use App\Mail\OrderShipped; 

class AdminController extends Controller
{
    // 1. 전체 주문 내역 조회 (영향 없음 - 원본 유지)
    public function getOrders()
    {
        $orders = Order::orderBy('created_at', 'desc')->get();
        return response()->json($orders);
    }

    // 🔴 2. 송장번호 입력, 배송 처리 및 [자동 재고 차감 + 배송완료 메일 발송 추가]
    public function shipOrder(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // 재고 자동 차감 로직
        if ($order->status !== 'shipped' && $order->status !== '発送済み') {
            $orderItems = DB::table('order_items')->where('order_id', $order->id)->get();
            foreach ($orderItems as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->stock = max(0, $product->stock - $item->quantity);
                    $product->save();
                }
            }
        }
        
        $order->tracking_number = $request->tracking_number;
        $order->status = '発送済み'; 
        $order->save();

        // 💡 [핵심 추가] 배송 처리가 완료된 후 고객(User)을 찾아 알림 메일을 발송합니다!
        $user = User::find($order->user_id);
        if ($user && $user->email) {
            try {
                Mail::to($user->email)->send(new OrderShipped($order, $user));
            } catch (\Exception $e) {
                \Log::error('Shipping Mail Send Error: ' . $e->getMessage());
                // 메일 발송 실패 시 프론트엔드로 에러 메시지를 던져줍니다.
                return response()->json([
                    'message' => '【メールエラー】 配送処理は完了しましたが、メール送信に失敗しました: ' . $e->getMessage(), 
                    'order' => $order
                ], 500); 
            }
        }

        return response()->json([
            'message' => '配送処理が完了し、お客様に発送完了メールを送信しました。(배송처리 및 메일발송 완료)', 
            'order' => $order
        ]);
    }

    // 3. 상품 신규 등록 (영향 없음 - 원본 유지)
    public function addProduct(Request $request)
    {
        $product = new Product();
        $product->product_id = $request->product_id; 
        $product->product_name = $request->product_name;
        $product->product_detail = $request->product_detail;
        $product->price = $request->price;
        $product->category = $request->category;
        $product->product_category = $request->product_category;
        $product->description_title = $request->description_title;
        $product->cv = $request->cv ? $request->cv : 0;
        $product->pv = $request->pv ? $request->pv : 0;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->image = '/storage/' . $path;
        }
        $product->save();

        return response()->json(['message' => '商品が登録されました。', 'product' => $product], 201);
    }

    // 4. 상품 삭제 (영향 없음 - 원본 유지)
    public function deleteProduct($id)
    {
        Product::destroy($id);
        return response()->json(['message' => '削除しました。']);
    }

    // 5. 전체 예약 내역 조회 (영향 없음 - 원본 유지)
    public function getReservations()
    {
        $reservations = Reservation::orderBy('date', 'desc')->orderBy('time', 'desc')->get();
        return response()->json($reservations);
    }

    // 6. 예약 상태 변경 및 메일 발송 (영향 없음 - 이전에 수정한 원본 유지)
    public function updateReservationStatus(Request $request, $id)
    {
        $reservation = Reservation::find($id);
        if (!$reservation) {
            return response()->json(['message' => '予約が見つかりません。'], 404);
        }

        try {
            // [조건 1] 예약이 '확정(confirmed)'으로 변경될 때
            if ($request->has('status') && $request->status === 'confirmed' && $reservation->status !== 'confirmed') {
                if (!empty($reservation->email)) {
                    Mail::to($reservation->email)->send(new ReservationConfirmed($reservation));
                }
            }

            // [조건 2] 예약이 '취소(cancelled)'로 변경될 때
            if ($request->has('status') && $request->status === 'cancelled' && $reservation->status !== 'cancelled') {
                if (!empty($reservation->email)) {
                    Mail::to($reservation->email)->send(new ReservationCancelled($reservation));
                }
            }

            // [조건 3] 예약 날짜 또는 시간이 변경될 때
            $isModified = false;
            if ($request->has('date') && $request->date != $reservation->date) {
                $reservation->date = $request->date;
                $isModified = true;
            }
            if ($request->has('time') && $request->time != $reservation->time) {
                $reservation->time = $request->time;
                $isModified = true;
            }
            if ($isModified && !empty($reservation->email)) {
                Mail::to($reservation->email)->send(new ReservationModified($reservation));
            }

            // 최종 상태 저장
            if ($request->has('status')) {
                $reservation->status = $request->status;
            }
            $reservation->save();

            return response()->json(['message' => 'ステータスを更新しました。', 'reservation' => $reservation]);
            
        } catch (\Exception $e) {
            \Log::error('Mail Send Error: ' . $e->getMessage());
            return response()->json(['message' => '【メールエラー】 ' . $e->getMessage()], 500);
        }
    }

    // 7. 모든 회원 목록 불러오기 (영향 없음 - 원본 유지)
    public function getUsers()
    {
        $users = User::orderBy('created_at', 'desc')->get();
        return response()->json($users);
    }

    // 8. 관리자 권한 부여/해제 (영향 없음 - 원본 유지)
    public function updateUserRole(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'ユーザーが見つかりません。'], 404);
        }

        $user->is_admin = $request->is_admin;
        if ($request->has('role')) {
            $user->role = $request->role;
        } else {
            $user->role = $request->is_admin ? 'admin' : 'user';
        }
        $user->save();

        return response()->json(['message' => '権限を更新しました。']);
    }

    // 9. 회원 계정 강제 삭제 (영향 없음 - 원본 유지)
    public function deleteUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => '既に削除されているか、見つかりません。'], 404);
        }
        if (auth()->id() == $user->id) {
            return response()->json(['message' => '現在ログイン中の自分自身のアカウントは削除できません。'], 403);
        }

        try {
            $user->addresses()->delete(); 
            if ($user->cart) {
                $user->cart()->delete(); 
            }
            $user->delete();
            return response()->json(['message' => 'アカウントを完全に削除しました。'], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'このユーザーは注文履歴が存在するため、システム保護のために削除できません。'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => '削除中にエラーが発生しました。'], 500);
        }
    }

    // 10. 상품 재고 수량 업데이트 (영향 없음 - 원본 유지)
    public function updateStock(Request $request, $id)
    {
        $request->validate(['stock' => 'required|integer|min:0']);
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => '商品が見つかりません。'], 404);
        }
        $product->stock = $request->stock;
        $product->save();
        return response()->json(['message' => '在庫を更新しました。']);
    }

    // 11. 매출 집계 가져오기 (영향 없음 - 원본 유지)
    public function getSalesSummary()
    {
        $orders = Order::all();
        $totalSales = $orders->sum('total_amount');
        $currentMonth = \Carbon\Carbon::now()->format('Y-m');
        $monthlySales = $orders->filter(function($order) use ($currentMonth) {
            return strpos($order->created_at, $currentMonth) === 0;
        })->sum('total_amount');

        $thirtyDaysAgo = \Carbon\Carbon::now()->subDays(30);
        $dailyOrders = $orders->where('created_at', '>=', $thirtyDaysAgo)
            ->groupBy(function($date) {
                return \Carbon\Carbon::parse($date->created_at)->format('Y-m-d');
            });

        $dailySales = [];
        foreach ($dailyOrders as $date => $dayOrders) {
            $dailySales[] = [
                'date' => $date, 'total' => $dayOrders->sum('total_amount'), 'count' => $dayOrders->count()
            ];
        }

        $yearlyMonthlyData = [];
        foreach ($orders as $order) {
            $year = \Carbon\Carbon::parse($order->created_at)->format('Y');
            $month = \Carbon\Carbon::parse($order->created_at)->format('m');
            if (!isset($yearlyMonthlyData[$year])) {
                for ($i = 1; $i <= 12; $i++) {
                    $m = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $yearlyMonthlyData[$year][$m] = ['total' => 0, 'count' => 0];
                }
            }
            $yearlyMonthlyData[$year][$month]['total'] += $order->total_amount;
            $yearlyMonthlyData[$year][$month]['count'] += 1;
        }
        krsort($yearlyMonthlyData);

        return response()->json([
            'total_sales' => $totalSales,
            'monthly_sales' => $monthlySales,
            'daily_sales' => array_values($dailySales),
            'yearly_monthly_sales' => $yearlyMonthlyData 
        ]);
    }
}