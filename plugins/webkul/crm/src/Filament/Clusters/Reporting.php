<?php

namespace Webkul\Crm\Filament\Clusters;

use Filament\Clusters\Cluster;
use Webkul\Support\Enums\NavigationGroup;
use Webkul\Support\Traits\HasClusterBreadcrumbs;

class Reporting extends Cluster
{
    use HasClusterBreadcrumbs;

    protected static ?string $slug = 'crm/reporting';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('crm::filament/clusters/reporting.navigation.title');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Crm;
    }
}
