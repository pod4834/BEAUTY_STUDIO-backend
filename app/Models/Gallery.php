<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    // 💡 이 부분이 반드시 들어가 있어야 에러가 나지 않습니다!!
    protected $fillable = [
        'title', 
        'description', 
        'before_image', 
        'after_image', 
        'product_ids'
    ];

    protected $casts = [
        'product_ids' => 'array',
    ];
}