<?php

namespace Webkul\Product\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Product\Filament\Resources\CategoryResource\Schemas\CategoryForm;
use Webkul\Product\Filament\Resources\CategoryResource\Schemas\CategoryInfolist;
use Webkul\Product\Filament\Resources\CategoryResource\Tables\CategoriesTable;
use Webkul\Product\Models\Category;
use Webkul\Support\Filament\Concerns\HasNavigationLabelTitles;

class CategoryResource extends Resource
{
    use HasNavigationLabelTitles;

    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isGloballySearchable = false;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('products::models/category.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('products::models/category.plural-title');
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CategoryInfolist::configure($schema);
    }
}
