<?php

namespace Webkul\Crm\Filament\Clusters;

use Filament\Clusters\Cluster;
use Webkul\Support\Enums\NavigationGroup;
use Webkul\Support\Traits\HasClusterBreadcrumbs;

class Sales extends Cluster
{
    use HasClusterBreadcrumbs;

    protected static ?string $slug = 'crm/sales';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('crm::filament/clusters/sales.navigation.title');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Crm;
    }
}
