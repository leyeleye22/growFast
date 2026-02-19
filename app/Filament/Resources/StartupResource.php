<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StartupResource\Pages;
use App\Models\Startup;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StartupResource extends Resource
{
    protected static ?string $model = Startup::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('tagline')->maxLength(150),
            \Filament\Forms\Components\Textarea::make('description')->maxLength(5000),
            DatePicker::make('founding_date'),
            TextInput::make('pitch_video_url')->url()->maxLength(500),
            TextInput::make('website')->url()->maxLength(255),
            TextInput::make('phone')->maxLength(30),
            TextInput::make('social_media')->url()->maxLength(500),
            TextInput::make('industry')->maxLength(100),
            Select::make('customer_type')->options([
                'B2B' => 'B2B',
                'B2C' => 'B2C',
                'B2B2B' => 'B2B2B',
                'B2B2C' => 'B2B2C',
                'B2G' => 'B2G',
                'nonprofit' => 'Nonprofit',
            ]),
            TextInput::make('stage')->maxLength(50),
            TextInput::make('country')->maxLength(3),
            TextInput::make('revenue_min')->numeric(),
            TextInput::make('revenue_max')->numeric(),
            Select::make('ownership_type')->options([
                'minority' => 'Minority',
                'women' => 'Women',
                'veteran' => 'Veteran',
                'diverse' => 'Diverse',
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('user.name')->label('Owner')->searchable(),
                TextColumn::make('industry')->searchable()->badge()->color('info'),
                TextColumn::make('stage')->badge()->color('gray'),
                TextColumn::make('country'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('industry')->options(fn () => Startup::query()->distinct()->pluck('industry', 'industry')->toArray()),
                SelectFilter::make('country'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('View'),
                    EditAction::make()->label('Edit'),
                    DeleteAction::make()->label('Delete'),
                    ForceDeleteAction::make()->label('Force delete'),
                    RestoreAction::make()->label('Restore'),
                ])->label('Actions')->icon('heroicon-m-ellipsis-vertical'),
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
            'index' => Pages\ListStartups::route('/'),
            'view' => Pages\ViewStartup::route('/{record}'),
            'edit' => Pages\EditStartup::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
}
