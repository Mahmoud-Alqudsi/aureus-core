<?php

namespace Webkul\Maintenance\Filament\Clusters;

use Filament\Clusters\Cluster;
use Webkul\Support\Traits\HasClusterBreadcrumbs;
use Webkul\Support\Enums\NavigationGroup;

class Maintenance extends Cluster
{
    use HasClusterBreadcrumbs;
    protected static ?string $slug = 'maintenance/maintenance';

    protected static ?int $navigationSort = -1;

    public static function getNavigationLabel(): string
    {
        return __('maintenance::filament/clusters/maintenance.navigation.title');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Maintenance;
    }
}
