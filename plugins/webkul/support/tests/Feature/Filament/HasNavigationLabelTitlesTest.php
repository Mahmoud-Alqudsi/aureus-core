<?php

use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Webkul\Support\Filament\Concerns\HasNavigationLabelTitles;

class NavigationLabelTitlesBaseResource extends Resource
{
    use HasNavigationLabelTitles;

    public static function getModelLabel(): string
    {
        return 'product';
    }

    public static function getPluralModelLabel(): string
    {
        return 'products';
    }
}

class NavigationLabelTitlesChildResource extends NavigationLabelTitlesBaseResource
{
    public static function getNavigationLabel(): string
    {
        return 'The Products';
    }
}

class NavigationLabelTitlesPropertyResource extends NavigationLabelTitlesBaseResource
{
    protected static ?string $navigationLabel = 'Catalog';
}

class NavigationLabelTitlesChildListPage extends ListRecords
{
    protected static string $resource = NavigationLabelTitlesChildResource::class;
}

it('falls back to the title-cased plural model label when no navigation label is defined', function () {
    expect(NavigationLabelTitlesBaseResource::getNavigationLabel())->toBe('Products')
        ->and(NavigationLabelTitlesBaseResource::getBreadcrumb())->toBe('Products')
        ->and(NavigationLabelTitlesBaseResource::getTitleCasePluralModelLabel())->toBe('Products');
});

it('makes the breadcrumb and title labels follow a navigation label defined on a child resource', function () {
    expect(NavigationLabelTitlesChildResource::getNavigationLabel())->toBe('The Products')
        ->and(NavigationLabelTitlesChildResource::getBreadcrumb())->toBe('The Products')
        ->and(NavigationLabelTitlesChildResource::getTitleCasePluralModelLabel())->toBe('The Products');
});

it('respects the static navigation label property', function () {
    expect(NavigationLabelTitlesPropertyResource::getNavigationLabel())->toBe('Catalog')
        ->and(NavigationLabelTitlesPropertyResource::getBreadcrumb())->toBe('Catalog');
});

it('keeps the plural model label untouched', function () {
    expect(NavigationLabelTitlesChildResource::getPluralModelLabel())->toBe('products');
});

it('gives list pages the navigation label as their title without overriding getTitle()', function () {
    expect((new NavigationLabelTitlesChildListPage)->getTitle())->toBe('The Products');
});
