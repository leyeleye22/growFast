<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\OpportunityStatus;
use App\Filament\Resources\OpportunityResource\Pages;
use App\Models\Opportunity;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestion';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            \Filament\Forms\Components\Textarea::make('description'),
            Select::make('funding_type')->options([
                'grant' => 'Grant',
                'equity' => 'Equity',
                'debt' => 'Debt',
                'prize' => 'Prize',
                'other' => 'Other',
            ]),
            DatePicker::make('deadline'),
            TextInput::make('funding_min')->numeric(),
            TextInput::make('funding_max')->numeric(),
            Select::make('status')->options(collect(OpportunityStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->name])),
            TextInput::make('external_url')->url(),
            TextInput::make('source'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('funding_type'),
                TextColumn::make('deadline')->date()->sortable(),
                TextColumn::make('funding_min')->money('USD'),
                TextColumn::make('funding_max')->money('USD'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('funding_type')->options([
                    'grant' => 'Grant',
                    'equity' => 'Equity',
                    'debt' => 'Debt',
                    'prize' => 'Prize',
                    'other' => 'Other',
                ]),
                SelectFilter::make('status')->options(collect(OpportunityStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->name])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
}
