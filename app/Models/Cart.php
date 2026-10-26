<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'cart_data'];
    
    // JSON 데이터를 자동으로 PHP 배열로 변환해 주는 기능
    protected $casts = [
        'cart_data' => 'array', 
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}