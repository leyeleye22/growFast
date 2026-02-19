<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Management';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->maxLength(255),
            TextInput::make('password')->password()->dehydrated(fn ($state) => filled($state)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->toggleable(),
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('email')->searchable()->sortable()->copyable(),
                TextColumn::make('email_verified_at')->dateTime()->sortable()->placeholder('Not verified')->badge()->color(fn ($state) => $state ? 'success' : 'gray'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('View'),
                    EditAction::make()->label('Edit'),
                    DeleteAction::make()->label('Delete'),
                ])->label('Actions')->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->filters([
                SelectFilter::make('verified')
                    ->options([
                        '1' => 'Verified',
                        '0' => 'Not verified',
                    ])
                    ->query(fn (Builder $q, array $data) => $data['value'] === '1'
                        ? $q->whereNotNull('email_verified_at')
                        : ($data['value'] === '0' ? $q->whereNull('email_verified_at') : $q)),
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
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
}
