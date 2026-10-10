<?php

namespace Webkul\Recruitment\Filament\Clusters;

use Filament\Clusters\Cluster;
use Webkul\Support\Traits\HasClusterBreadcrumbs;
use Filament\Panel;
use Webkul\Support\Enums\NavigationGroup;

class Applications extends Cluster
{
    use HasClusterBreadcrumbs;
    protected static ?int $navigationSort = 2;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'recruitments/applications';
    }

    public static function getNavigationLabel(): string
    {
        return __('recruitments::filament/clusters/applications.navigation.title');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Recruitment;
    }
}
