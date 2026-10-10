<?php

use Filament\Clusters\Cluster;
use Webkul\Support\Traits\HasClusterBreadcrumbs;

class DummyClusterWithTrait extends Cluster
{
    use HasClusterBreadcrumbs;

    public static function getNavigationLabel(): string
    {
        return 'التسمية المترجمة';
    }
}

class DummyClusterWithOverride extends Cluster
{
    use HasClusterBreadcrumbs;

    public static function getNavigationLabel(): string
    {
        return 'القائمة الجانبية';
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return 'مسار تنقل مخصص';
    }
}

it('returns navigation label as cluster breadcrumb when using HasClusterBreadcrumbs trait', function () {
    expect(DummyClusterWithTrait::getClusterBreadcrumb())->toBe('التسمية المترجمة');
});

it('allows overriding cluster breadcrumb when using HasClusterBreadcrumbs trait', function () {
    expect(DummyClusterWithOverride::getClusterBreadcrumb())->toBe('مسار تنقل مخصص');
});
