<?php

namespace App\Filament\Resources\Inventories;

use App\Models\Product;
use Filament\Resources\Resource;
use BackedEnum;

class InventoryResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = '在庫管理';
    protected static ?int $navigationSort = 6;
}