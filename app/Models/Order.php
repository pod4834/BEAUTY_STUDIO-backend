<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // 💡 하나의 주문(Order)은 여러 개의 상세 내역(OrderItem)을 가집니다.
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}