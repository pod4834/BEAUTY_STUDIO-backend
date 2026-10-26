<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('products')->insert([
            [
                'name' => '셀비아 인텐시브 리ペア 세럼',
                'description' => '피부 깊숙이 영양을 채워주는 고농축 안티에이징 세럼입니다.',
                'price' => 45000,
                'stock' => 100,
                'image_url' => 'https://via.placeholder.com/400x400.png?text=Cellvia+Serum',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '셀비아 수분 캡슐 크림',
                'description' => '24시간 마르지 않는 촉촉함을 유지해주는 산뜻한 수분 크림.',
                'price' => 32000,
                'stock' => 50,
                'image_url' => 'https://via.placeholder.com/400x400.png?text=Cellvia+Cream',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '셀비아 퓨어 클렌징 오일',
                'description' => '블랙헤드와 메이크업 잔여물을 말끔하게 지워주는 저자극 클렌징 오일.',
                'price' => 28000,
                'stock' => 200,
                'image_url' => 'https://via.placeholder.com/400x400.png?text=Cleansing+Oil',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '셀비아 리바이탈라이징 토너',
                'description' => '세안 후 피부 결을 매끄럽게 정돈해주는 첫 단계 토너.',
                'price' => 21000,
                'stock' => 150,
                'image_url' => 'https://via.placeholder.com/400x400.png?text=Cellvia+Toner',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '셀비아 마일드 선블록 (SPF50+)',
                'description' => '백탁 현상 없이 촉촉하게 발리는 데일리 자외선 차단제.',
                'price' => 25000,
                'stock' => 80,
                'image_url' => 'https://via.placeholder.com/400x400.png?text=Sunblock',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}