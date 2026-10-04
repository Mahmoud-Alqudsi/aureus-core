<?php

namespace Webkul\Support\Filament\Resources;

use Filament\Resources\Resource;
use Webkul\Support\Filament\Concerns\HasNavigationLabelTitles;
use Webkul\Support\Models\State;

class StateResource extends Resource
{
    use HasNavigationLabelTitles;

    protected static ?string $model = State::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isGloballySearchable = false;

    protected static ?string $recordTitleAttribute = 'name';
}
