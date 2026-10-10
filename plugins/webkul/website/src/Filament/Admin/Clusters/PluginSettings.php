<?php

namespace Webkul\Website\Filament\Admin\Clusters;

use Filament\Clusters\Cluster;
use Webkul\Support\Traits\HasClusterBreadcrumbs;
use Webkul\Support\Enums\NavigationGroup;

class PluginSettings extends Cluster
{
    use HasClusterBreadcrumbs;
    protected static ?string $slug = 'website/settings';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('website::filament/app.navigation.settings.label');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Website;
    }
}
