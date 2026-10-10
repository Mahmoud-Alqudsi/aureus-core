<?php

namespace Webkul\Invoice\Filament\Clusters;

use Filament\Clusters\Cluster;
use Webkul\Support\Traits\HasClusterBreadcrumbs;
use Webkul\Support\Enums\NavigationGroup;

class Configuration extends Cluster
{
    use HasClusterBreadcrumbs;
    protected static ?string $slug = 'invoices/configurations';

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Invoice;
    }
}
