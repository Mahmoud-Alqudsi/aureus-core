<?php

namespace Webkul\Support\Filament\Concerns;

use Illuminate\Support\Str;

/**
 * Makes the page title, breadcrumb and relation tab title of a Filament resource
 * follow its navigation label.
 *
 * Filament resolves all of them through getTitleCasePluralModelLabel(), so a
 * resource only has to define getNavigationLabel() (for example the definite
 * Arabic form) while getPluralModelLabel() keeps the indefinite form.
 *
 * Attach it to the top-most resource of an inheritance chain only: a trait method
 * overrides the inherited one, so using it on a child would shadow a
 * getNavigationLabel() defined on an intermediate parent.
 */
trait HasNavigationLabelTitles
{
    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? Str::ucwords(static::getPluralModelLabel());
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return static::getNavigationLabel();
    }
}
