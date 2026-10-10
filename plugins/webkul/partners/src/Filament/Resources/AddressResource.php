<?php

namespace Webkul\Partner\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Partner\Filament\Resources\AddressResource\Schemas\AddressForm;
use Webkul\Partner\Filament\Resources\AddressResource\Tables\AddressesTable;
use Webkul\Partner\Models\Address;
use Webkul\Support\Filament\Concerns\HasNavigationLabelTitles;

class AddressResource extends Resource
{
    use HasNavigationLabelTitles;

    protected static ?string $model = Address::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('partners::filament/resources/address.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return AddressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AddressesTable::configure($table);
    }
}
