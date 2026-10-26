<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number'),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('total_cv')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_pv')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('결제완료'),
                TextInput::make('tracking_number'),
                TextInput::make('payment_method')
                    ->required()
                    ->default('bank_transfer'),
            ]);
    }
}
