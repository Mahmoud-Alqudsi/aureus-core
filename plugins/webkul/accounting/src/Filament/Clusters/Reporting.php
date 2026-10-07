<?php

namespace Webkul\Accounting\Filament\Clusters;

use Filament\Clusters\Cluster;
use Webkul\Support\Enums\NavigationGroup;
use Webkul\Support\Traits\HasClusterBreadcrumbs;

class Reporting extends Cluster
{
    use HasClusterBreadcrumbs;

    protected static ?string $slug = 'accounting/reporting';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('accounting::filament/clusters/reporting.navigation.title');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Accounting;
    }
}
