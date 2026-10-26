<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DummyOrderSeeder extends Seeder
{
    public function run()
    {
        // 첫 번째 회원 찾기
        $user = DB::table('users')->first();

        if (!$user) {
            echo "❌ 유저 정보가 없습니다. 브라우저에서 회원가입을 먼저 진행해주세요.\n";
            return;
        }

        // 1. 첫 번째 주문 (발송 준비 중)
        $order1Id = DB::table('orders')->insertGetId([
            'user_id' => $user->id,
            'total_amount' => 18500,
            'status' => '発送準備中', // 발송 준비 중
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        // 1번 주문의 상세 상품 3개
        DB::table('order_items')->insert([
            ['order_id' => $order1Id, 'product_id' => 1, 'product_name' => 'CELLVIA クレンジングフォーム', 'quantity' => 2, 'price' => 4000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['order_id' => $order1Id, 'product_id' => 2, 'product_name' => 'CELLVIA UVクリーム', 'quantity' => 1, 'price' => 3500, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['order_id' => $order1Id, 'product_id' => 3, 'product_name' => 'CELLVIA ナイトクリーム', 'quantity' => 1, 'price' => 7000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        ]);

        // 2. 두 번째 주문 (배송 완료, 3일 전)
        $order2Id = DB::table('orders')->insertGetId([
            'user_id' => $user->id,
            'total_amount' => 25000,
            'status' => '配送済み', // 배송 완료
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(3)
        ]);

        // 2번 주문의 상세 상품 2개
        DB::table('order_items')->insert([
            ['order_id' => $order2Id, 'product_id' => 4, 'product_name' => 'CELLVIA スキンケアセット', 'quantity' => 1, 'price' => 15000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['order_id' => $order2Id, 'product_id' => 5, 'product_name' => 'CELLVIA エッセンス', 'quantity' => 2, 'price' => 5000, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        ]);

        echo "✅ DB에 더미 주문 데이터 등록 완료!\n";
    }
}