<?php

namespace Webkul\Product\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Product\Filament\Resources\PackagingResource\Schemas\PackagingForm;
use Webkul\Product\Filament\Resources\PackagingResource\Schemas\PackagingInfolist;
use Webkul\Product\Filament\Resources\PackagingResource\Tables\PackagingsTable;
use Webkul\Product\Models\Packaging;
use Webkul\Support\Filament\Concerns\HasNavigationLabelTitles;

class PackagingResource extends Resource
{
    use HasNavigationLabelTitles;

    protected static ?string $model = Packaging::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('products::filament/resources/packaging.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return PackagingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackagingsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PackagingInfolist::configure($schema);
    }
}
