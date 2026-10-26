<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('category'),
                TextInput::make('product_category')
                    ->default('single'),
                TextInput::make('product_id'),
                TextInput::make('product_name')
                    ->required(),
                TextInput::make('description_title'),
                Textarea::make('product_detail')
                    ->columnSpanFull(),
                TextInput::make('detail_url')
                    ->url(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('cv')
                    ->numeric(),
                TextInput::make('pv')
                    ->numeric(),
                TextInput::make('img_url')
                    ->url(),
            ]);
    }
}
