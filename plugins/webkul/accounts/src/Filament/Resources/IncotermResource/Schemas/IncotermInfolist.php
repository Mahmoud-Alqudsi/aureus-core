<?php

namespace Webkul\Account\Filament\Resources\IncotermResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class IncotermInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code')
                    ->label(__('accounts::filament/resources/incoterm.infolist.entries.code'))
                    ->placeholder('-'),
                TextEntry::make('name')
                    ->label(__('accounts::filament/resources/incoterm.infolist.entries.name'))
                    ->placeholder('-'),
            ]);
    }
}
