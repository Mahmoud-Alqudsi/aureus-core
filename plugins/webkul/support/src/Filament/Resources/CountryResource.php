<?php

namespace Webkul\Support\Filament\Resources;

use Filament\Resources\Resource;
use Webkul\Support\Filament\Concerns\HasNavigationLabelTitles;
use Webkul\Support\Models\Country;

class CountryResource extends Resource
{
    use HasNavigationLabelTitles;

    protected static ?string $model = Country::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isGloballySearchable = false;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('support::filament/resources/country.model-label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('support::filament/resources/country.plural-model-label');
    }
}
