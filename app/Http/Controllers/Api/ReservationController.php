<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use Illuminate\Database\QueryException;

// 💡 메일 발송을 위한 파사드 및 클래스 추가
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationReceived; 
use App\Mail\ReservationModified; 
use App\Mail\ReservationCancelled; // 🔴 [추가됨] 취소 완료 알림 메일 클래스

class ReservationController extends Controller
{
    // [1] 이미 예약된 시간 목록 불러오기 (GET)
    public function getBookedTimes(Request $request)
    {
        $date = $request->query('date');
        
        if (!$date) {
            return response()->json([], 400); // 날짜가 없으면 빈 배열 반환
        }

        // 💡 취소된(cancelled) 예약은 제외하고 예약된 시간만 가져옵니다! (빈자리 다시 오픈)
        $bookedTimes = Reservation::where('date', $date)
                                  ->where('status', '!=', 'cancelled')
                                  ->pluck('time');
        
        return response()->json($bookedTimes);
    }

    // [2] 새로운 예약 저장하기 및 이메일 발송 (POST)
    public function store(Request $request)
    {
        // 1. 필수 값 유효성 검사 (Validation)
        $validated = $request->validate([
            'menu_id' => 'required|integer',
            'date' => 'required|date',
            'time' => 'required|string',
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:150',
        ]);

        try {
            // 2. DB에 예약 정보 저장
            $reservation = Reservation::create([
                'menu_id'         => $request->menu_id,
                'date'            => $request->date,
                'time'            => $request->time,
                'name'            => $request->name,
                'phone'           => $request->phone,
                'email'           => $request->email,
                'memo'            => $request->memo, 
                'introducer_name' => $request->introducerName, 
                'introducer_id'   => $request->introducerId,   
                'status'          => 'pending', // 🔴 처음 접수 시 무조건 '대기중' 상태로 저장!
            ]);

            // 💡 3. 저장 성공 후, 고객의 이메일로 '예약 접수 안내 메일' 자동 발송 실행!
            try {
                Mail::to($reservation->email)->send(new ReservationReceived($reservation));
            } catch (\Exception $e) {
                // 메일 발송만 실패한 경우 (예약은 DB에 정상 저장됨)
                \Log::error('Mail Send Error: ' . $e->getMessage());
                return response()->json([
                    'message' => '予約は完了しましたが、メール送信に失敗しました。(예약 완료 / 이메일 발송 실패)',
                    'reservation' => $reservation
                ], 201);
            }

            return response()->json([
                'message' => '予約を受け付けました。(예약이 접수되었습니다.)', 
                'reservation' => $reservation
            ], 201);

        } catch (QueryException $e) {
            // 💡 4. DB 고유키(Unique) 제약 조건 위반 (중복 예약 방지)
            if ($e->getCode() == '23000') {
                return response()->json([
                    'message' => '申し訳ありません。ご希望の時間はすでに予約が埋まっております。(죄송합니다. 원하시는 시간은 이미 예약이 마감되었습니다.)'
                ], 409); 
            }
            
            // 기타 서버 에러
            \Log::error('Reservation Save Error: ' . $e->getMessage());
            return response()->json(['message' => '予約処理中にエラーが発生しました。'], 500);
        }
    }

    // 🔴 [3] 특정 예약 정보 불러오기 (GET) - 고객 관리 페이지용
    public function show($id)
    {
        $reservation = Reservation::find($id);
        if (!$reservation) {
            return response()->json(['message' => '予約が見つかりません。'], 404);
        }
        return response()->json($reservation);
    }

    // 🔴 [4] 예약 날짜/시간 변경하기 (PUT) - 고객 관리 페이지용
    public function update(Request $request, $id)
    {
        $reservation = Reservation::find($id);
        if (!$reservation) {
            return response()->json(['message' => '予約が見つかりません。'], 404);
        }

        $request->validate([
            'date' => 'required|date',
            'time' => 'required|string'
        ]);

        try {
            // DB에 변경된 날짜와 시간을 업데이트
            $reservation->date = $request->date;
            $reservation->time = $request->time;
            $reservation->save();

            // 💡 변경 완료 후 고객에게 알림 메일 발송
            if ($reservation->email) {
                try {
                    Mail::to($reservation->email)->send(new ReservationModified($reservation));
                } catch (\Exception $e) {
                    \Log::error('Modification Mail Send Error: ' . $e->getMessage());
                    // 메일 전송에 실패해도 클라이언트 화면에는 변경 성공으로 표시
                }
            }

            return response()->json(['message' => '予約日時を変更しました。', 'reservation' => $reservation]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return response()->json(['message' => 'ご希望の時間はすでに予約が埋まっております。'], 409);
            }
            return response()->json(['message' => 'エラーが発生しました。'], 500);
        }
    }

    // 🔴 [5] 예약 취소하기 (PUT) - 고객 관리 페이지용
    public function cancel($id)
    {
        $reservation = Reservation::find($id);
        if (!$reservation) {
            return response()->json(['message' => '予約が見つかりません。'], 404);
        }

        // 상태를 'cancelled'로 변경
        $reservation->status = 'cancelled';
        $reservation->save();

        // 💡 [신규 추가됨] 취소 처리 완료 후 고객에게 취소 알림 메일 발송!
        if ($reservation->email) {
            try {
                Mail::to($reservation->email)->send(new ReservationCancelled($reservation));
            } catch (\Exception $e) {
                \Log::error('Cancellation Mail Send Error: ' . $e->getMessage());
                // 메일 전송에 실패해도 클라이언트 화면에는 취소 성공으로 표시
            }
        }

        return response()->json(['message' => 'ご予約をキャンセルしました。', 'reservation' => $reservation]);
    }
}