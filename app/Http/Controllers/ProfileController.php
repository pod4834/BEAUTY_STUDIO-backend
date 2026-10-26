<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * 💡 [회원정보 수정 API]
     * React 프론트엔드에서 넘겨받은 프로필(청구지 통합 및 카나 포함) 정보를
     * users 테이블에 안전하게 업데이트합니다.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // 💡 프론트엔드에서 넘어온 값(camelCase)을 DB 컬럼명(snake_case)에 맞게 매핑하여 업데이트합니다.
        // 만약 프론트엔드에서 특정 필드를 보내지 않았다면, 기존의 값($user->컬럼명)을 그대로 유지합니다.
        $user->update([
            'last_name'       => $request->input('lastName', $user->last_name),
            'first_name'      => $request->input('firstName', $user->first_name),
            'last_name_kana'  => $request->input('lastNameKana', $user->last_name_kana),
            'first_name_kana' => $request->input('firstNameKana', $user->first_name_kana),
            'zip'             => $request->input('zip', $user->zip),
            'address'         => $request->input('address', $user->address),
            'phone'           => $request->input('phone', $user->phone),
            'company'         => $request->input('company', $user->company),
        ]);

        return response()->json([
            'message' => '会員情報が保存されました。',
            'user'    => $user
        ], 200);
    }
}