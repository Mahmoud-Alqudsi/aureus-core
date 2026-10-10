<?php

namespace Webkul\Support\Traits;

trait HasClusterBreadcrumbs
{
    public static function getClusterBreadcrumb(): ?string
    {
        return static::getNavigationLabel();
    }
}
