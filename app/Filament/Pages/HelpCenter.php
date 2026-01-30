<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HelpCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string $view = 'filament.pages.help-center';
    
    protected static ?string $navigationLabel = '📖 Help Center';
    
    protected static ?string $title = 'Help Center & FAQs';
    
    protected static ?int $navigationSort = 100;
    
    protected static ?string $navigationGroup = 'Help & Documentation';
}
