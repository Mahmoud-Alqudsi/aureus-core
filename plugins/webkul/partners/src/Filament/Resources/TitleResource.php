<?php

namespace Webkul\Partner\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Partner\Filament\Resources\TitleResource\Schemas\TitleForm;
use Webkul\Partner\Filament\Resources\TitleResource\Tables\TitlesTable;
use Webkul\Partner\Models\Title;
use Webkul\Support\Filament\Concerns\HasNavigationLabelTitles;

class TitleResource extends Resource
{
    use HasNavigationLabelTitles;

    protected static ?string $model = Title::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('partners::filament/resources/title.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return TitleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TitlesTable::configure($table);
    }
}
