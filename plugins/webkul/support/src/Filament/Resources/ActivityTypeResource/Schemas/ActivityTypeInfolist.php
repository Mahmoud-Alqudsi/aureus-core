<?php

namespace Webkul\Support\Filament\Resources\ActivityTypeResource\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Support\Enums\ActivityChainingType;
use Webkul\Support\Enums\ActivityDecorationType;
use Webkul\Support\Enums\ActivityDelayFrom;
use Webkul\Support\Enums\ActivityDelayUnit;
use Webkul\Support\Enums\ActivityTypeAction;

class ActivityTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 3])
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make(__('support::filament/resources/activity-type.infolist.sections.activity-type-details.title'))
                                    ->schema([
                                        TextEntry::make('name')
                                            ->icon('heroicon-o-clipboard-document-list')
                                            ->placeholder('—')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.activity-type-details.entries.name')),
                                        TextEntry::make('category')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.activity-type-details.entries.action'))
                                            ->icon('heroicon-o-tag')
                                            ->placeholder('—')
                                            ->formatStateUsing(fn ($state) => ActivityTypeAction::options()[$state]),
                                        TextEntry::make('defaultUser.name')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.activity-type-details.entries.default-user'))
                                            ->icon('heroicon-o-user')
                                            ->placeholder('—'),
                                        TextEntry::make('plugin')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.activity-type-details.entries.plugin'))
                                            ->icon('heroicon-o-puzzle-piece')
                                            ->placeholder('—'),
                                    ])->columns(2),
                                Section::make(__('support::filament/resources/activity-type.infolist.sections.delay-information.title'))
                                    ->schema([
                                        TextEntry::make('summary')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.activity-type-details.entries.summary'))
                                            ->placeholder('—')
                                            ->columnSpanFull(),
                                        TextEntry::make('default_note')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.activity-type-details.entries.note'))
                                            ->html()
                                            ->placeholder('—')
                                            ->columnSpanFull(),
                                    ]),
                                Section::make(__('support::filament/resources/activity-type.infolist.sections.delay-information.title'))
                                    ->schema([
                                        TextEntry::make('delay_count')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.delay-information.entries.delay-count'))
                                            ->icon('heroicon-o-clock')
                                            ->placeholder('—')
                                            ->numeric(),
                                        TextEntry::make('delay_unit')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.delay-information.entries.delay-unit'))
                                            ->icon('heroicon-o-calendar')
                                            ->placeholder('—')
                                            ->formatStateUsing(fn ($state) => ActivityDelayUnit::options()[$state]),
                                        TextEntry::make('delay_from')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.delay-information.entries.delay-form'))
                                            ->icon('heroicon-o-arrow-right')
                                            ->placeholder('—')
                                            ->formatStateUsing(fn ($state) => ActivityDelayFrom::options()[$state]),
                                    ])->columns(2),
                            ])->columnSpan(2),
                        Group::make()
                            ->schema([
                                Section::make(__('support::filament/resources/activity-type.infolist.sections.advanced-information.title'))
                                    ->schema([
                                        TextEntry::make('icon')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.advanced-information.entries.icon'))
                                            ->icon(fn ($record) => $record->icon)
                                            ->placeholder('—'),
                                        TextEntry::make('decoration_type')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.advanced-information.entries.decoration-type'))
                                            ->icon('heroicon-o-paint-brush')
                                            ->formatStateUsing(fn ($state) => ActivityDecorationType::options()[$state])
                                            ->placeholder('—'),
                                        TextEntry::make('chaining_type')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.advanced-information.entries.chaining-type'))
                                            ->icon('heroicon-o-link')
                                            ->formatStateUsing(fn ($state) => ActivityChainingType::options()[$state])
                                            ->placeholder('—'),
                                        TextEntry::make('suggestedActivityTypes.name')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.advanced-information.entries.suggest'))
                                            ->icon('heroicon-o-list-bullet')
                                            ->placeholder('—')
                                            ->listWithLineBreaks(),
                                        TextEntry::make('triggeredNextType.name')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.advanced-information.entries.trigger'))
                                            ->icon('heroicon-o-forward')
                                            ->placeholder('—'),
                                    ]),
                                Section::make(__('support::filament/resources/activity-type.infolist.sections.status-and-configuration-information.title'))
                                    ->schema([
                                        IconEntry::make('is_active')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.status-and-configuration-information.entries.status')),
                                        IconEntry::make('keep_done')
                                            ->label(__('support::filament/resources/activity-type.infolist.sections.status-and-configuration-information.entries.keep-done-activities')),
                                    ]),
                            ])->columnSpan(1),
                    ]),
            ])
            ->columns(1);
    }
}
