<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;

class MailSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'メール設定';
    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.mail-settings';
}