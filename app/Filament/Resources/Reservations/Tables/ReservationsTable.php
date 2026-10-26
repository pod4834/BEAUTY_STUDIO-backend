<?php

namespace App\Filament\Resources\Reservations\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('お名前')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('電話番号')
                    ->searchable(),

                TextColumn::make('date')
                    ->label('予約日')
                    ->sortable(),

                TextColumn::make('time')
                    ->label('予約時間'),

                TextColumn::make('status')
                    ->label('ステータス')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('created_at')
                    ->label('受付日時')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }
}