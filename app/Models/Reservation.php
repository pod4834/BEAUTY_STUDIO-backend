<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'date',
        'time',
        'name',
        'phone',
        'email',
        'memo',
        'introducer_name',
        'introducer_id',
        'status' // 💡 추가됨!
    ];
}