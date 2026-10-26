<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * 💡 [다중 배송지 불러오기 API]
     * 로그인한 유저의 배송지 목록을 가져옵니다.
     */
    public function index(Request $request)
    {
        // 기본 배송지(is_default = true)가 무조건 배열의 맨 위로 오도록 정렬하여 가져옵니다.
        $addresses = $request->user()->addresses()
                             ->where('type', 'shipping')
                             ->orderBy('is_default', 'desc')
                             ->orderBy('id', 'desc')
                             ->get();
        
        // React 프론트엔드의 camelCase 데이터 형식에 맞춰 변환 (카타카나 필드 포함)
        $formatted = $addresses->map(function($addr) {
            return [
                'id'            => $addr->id,
                'lastName'      => (string)$addr->last_name,
                'firstName'     => (string)$addr->first_name,
                'lastNameKana'  => (string)$addr->last_name_kana,
                'firstNameKana' => (string)$addr->first_name_kana,
                'zip'           => (string)$addr->zip,
                'address'       => (string)$addr->address,
                'phone'         => (string)$addr->phone,
                'company'       => (string)$addr->company,
                'isDefault'     => (bool)$addr->is_default
            ];
        });

        return response()->json($formatted);
    }

    /**
     * 💡 [다중 배송지 저장 API]
     * React에서 편집한 배송지 배열 전체를 통째로 전달받아 DB를 동기화합니다.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // 프론트엔드에서 보낸 addresses 배열 (없으면 빈 배열 처리)
        $addressesData = $request->input('addresses', []);

        // 1. 기존에 저장되어 있던 사용자의 배송지를 모두 삭제하여 찌꺼기를 없앱니다 (초기화)
        $user->addresses()->where('type', 'shipping')->delete();

        // 2. 프론트엔드에서 보내준 최신 배열 데이터를 바탕으로 새로 모두 생성합니다.
        foreach ($addressesData as $data) {
            UserAddress::create([
                'user_id'         => $user->id,
                'type'            => 'shipping',
                'last_name'       => (string)($data['lastName'] ?? ''),
                'first_name'      => (string)($data['firstName'] ?? ''),
                'last_name_kana'  => (string)($data['lastNameKana'] ?? ''),
                'first_name_kana' => (string)($data['firstNameKana'] ?? ''),
                'zip'             => (string)($data['zip'] ?? ''),
                'address'         => (string)($data['address'] ?? ''),
                'phone'           => (string)($data['phone'] ?? ''),
                'company'         => (string)($data['company'] ?? ''),
                'is_default'      => (bool)($data['isDefault'] ?? false),
            ]);
        }

        return response()->json(['message' => '配送先住所が保存されました。'], 200);
    }
}