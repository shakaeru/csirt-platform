<?php

namespace App\Filament\Resources\Roles;

use App\Enums\Permission;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\Role;
use App\Support\Access\GrantRules;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Role dan hak aksesnya (App\Enums\Permission). Aturan ubah/hapus: App\Policies\RolePolicy. */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Akses';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'role';

    protected static ?string $pluralModelLabel = 'Role';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama role')
                    ->placeholder('mis. Editor, Pengelola Struktur')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('description')
                    ->label('Keterangan')
                    ->helperText('Opsional, mis. jabatan yang biasanya memegang role ini.')
                    ->maxLength(255),
                CheckboxList::make('permissions')
                    ->label('Hak akses')
                    ->helperText('Hanya hak akses yang Anda miliki sendiri yang bisa dicentang.')
                    ->options(fn (): array => GrantRules::grantableOptions())
                    ->descriptions(Permission::descriptions())
                    ->rule(fn (): \Closure => GrantRules::permissions())
                    ->default([])
                    ->columns(2)
                    ->bulkToggleable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Role')
                    ->description(fn (Role $record): ?string => $record->description)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('permissions')
                    ->label('Hak akses')
                    ->state(fn (Role $record): array => $record->is_super
                        ? ['Semua hak akses']
                        : array_map(fn (Permission $permission): string => $permission->label(), $record->permissionList()))
                    ->badge()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->wrap(),
                TextColumn::make('users_count')
                    ->label('Pengguna')
                    ->counts('users')
                    ->numeric(),
            ])
            ->defaultSort('id')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
