<?php

namespace Webkul\Crm\Filament\Clusters;

use Filament\Clusters\Cluster;
use Webkul\Support\Enums\NavigationGroup;
use Webkul\Support\Traits\HasClusterBreadcrumbs;

class Leads extends Cluster
{
    use HasClusterBreadcrumbs;

    protected static ?string $slug = 'crm/leads';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('crm::filament/clusters/leads.navigation.title');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Crm;
    }
}
