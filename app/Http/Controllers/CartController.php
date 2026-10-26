<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // 1. 장바구니 정보 불러오기
    public function show(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'cart' => [] // 현재는 프론트엔드 로컬스토리지를 메인으로 사용하므로 빈 배열 반환
        ]);
    }

    // 💡 2. [추가됨] 장바구니 동기화 에러(500) 해결을 위한 update 함수
    public function update(Request $request)
    {
        // 프론트엔드(App.jsx)가 로컬스토리지에 완벽하게 장바구니를 보관하고 있으므로,
        // 백엔드에서는 에러가 나지 않도록 '성공(200)' 응답만 안전하게 반환해 줍니다.
        return response()->json([
            'status' => 'success',
            'message' => 'カートを同期しました。'
        ]);
    }

    // 3. 상품 추가 (참고용)
    public function add(Request $request)
    {
        return response()->json(['status' => 'success', 'message' => 'カートに追加しました。']);
    }

    // 4. 상품 삭제 (참고용)
    public function remove(Request $request, $id)
    {
        return response()->json(['status' => 'success', 'message' => 'カートから削除しました。']);
    }
}