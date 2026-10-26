<?php

use Illuminate\Support\Facades\Route;
use App\Models\Reservation;
use App\Mail\ReservationReceived; 
use App\Mail\ReservationConfirmed;

Route::get('/', function () {
    return view('welcome');
});

// 🔴 (이전에 임시로 넣었던 강제 api 로드 코드는 삭제했습니다. 이제 라라벨이 스스로 켭니다.)

// ----------------------------------------------------
// 🔴 [이메일 디자인 미리보기 라우트]
// ----------------------------------------------------

Route::get('/preview-email/received', function () {
    $reservation = Reservation::latest()->first();

    if (!$reservation) {
        $reservation = new Reservation([
            'name' => 'テスト(테스트)',
            'date' => '2026-09-10',
            'time' => '14:30',
            'phone' => '090-1234-5678',
            'menu_id' => 1
        ]);
    }

    return new ReservationReceived($reservation);
});

Route::get('/preview-email/confirmed', function () {
    $reservation = Reservation::latest()->first();

    if (!$reservation) {
        $reservation = new Reservation([
            'name' => 'テスト(테스트)',
            'date' => '2026-09-10',
            'time' => '14:30',
            'phone' => '090-1234-5678',
            'menu_id' => 1
        ]);
    }

    return new ReservationConfirmed($reservation);
});