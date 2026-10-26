<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * 프론트엔드/컨트롤러에서 대량 할당(Mass Assignment) 저장을 허용할 컬럼 목록
     */
    protected $fillable = [
        'code',
        'product_code',
        'product_name', 
        'category',
        'product_category',
        'price', 
        'cv',
        'pv',
        'description_title',
        'product_detail', 
        'image', 
        'image_url',
        'img_url',
        'stock',
    ];

    /**
     * 데이터형 자동 변환 (수치형 안전성 확보)
     */
    protected $casts = [
        'price' => 'integer',
        'cv' => 'integer',
        'pv' => 'integer',
        'stock' => 'integer',
    ];
}