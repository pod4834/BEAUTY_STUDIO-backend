<?php

namespace App\Filament\Resources\UserAddresses;

use App\Filament\Resources\UserAddresses\Pages\CreateUserAddress;
use App\Filament\Resources\UserAddresses\Pages\EditUserAddress;
use App\Filament\Resources\UserAddresses\Pages\ListUserAddresses;
use App\Filament\Resources\UserAddresses\Schemas\UserAddressForm;
use App\Filament\Resources\UserAddresses\Tables\UserAddressesTable;
use App\Models\UserAddress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserAddressResource extends Resource
{
    protected static ?string $model = UserAddress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UserAddressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserAddressesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserAddresses::route('/'),
            'create' => CreateUserAddress::route('/create'),
            'edit' => EditUserAddress::route('/{record}/edit'),
        ];
    }
}
