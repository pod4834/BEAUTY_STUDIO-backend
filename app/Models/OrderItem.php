<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    // 💡 하나의 상세 내역(OrderItem)은 어떤 상품(Product)인지 정보를 가집니다.
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}