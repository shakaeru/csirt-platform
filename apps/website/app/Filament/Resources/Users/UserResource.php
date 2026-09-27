<?php

namespace App\Filament\Resources\Users;

use App\Enums\Permission;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Role;
use App\Models\User;
use App\Support\Access\GrantRules;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * Akun panel admin. Siapa boleh mengurus siapa: App\Policies\UserPolicy; siapa boleh memberi
 * role/hak akses apa: App\Support\Access\GrantRules. Akun yang dibuat di sini langsung terverifikasi
 * (dibuat operator, seperti admin:create); password awal disampaikan langsung ke pemiliknya dan
 * diganti di /profile.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Akses';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $self = fn (?User $record): bool => $record?->is(auth()->user()) ?? false;

        return $schema
            ->components([
                Section::make('Akun')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            // Email disimpan huruf kecil: cek duplikat juga tanpa membedakan huruf besar,
                            // supaya "Budi@..." tidak lolos validasi lalu gagal di unique index database.
                            ->rule(fn (?User $record): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                                $taken = User::query()
                                    ->whereRaw('lower(email) = ?', [Str::lower(trim((string) $value))])
                                    ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                    ->exists();
                                if ($taken) {
                                    $fail('Email ini sudah dipakai akun lain.');
                                }
                            })
                            ->dehydrateStateUsing(fn (string $state): string => Str::lower(trim($state))),
                        TextInput::make('password')
                            ->label(fn (string $operation): string => $operation === 'create' ? 'Password awal' : 'Password baru')
                            ->helperText(fn (string $operation): string => $operation === 'create'
                                ? 'Min. 12 karakter, huruf dan angka. Sampaikan langsung ke pemiliknya; ganti sendiri di /profile.'
                                : 'Kosongkan bila tidak diganti.')
                            ->password()
                            ->revealable()
                            ->rule(Password::min(12)->letters()->numbers())
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->confirmed()
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->label('Ulangi password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false),
                    ]),
                Section::make('Akses panel')
                    ->description(fn (?User $record): ?string => $self($record)
                        ? 'Role dan hak akses akun Anda sendiri hanya bisa diubah pengguna lain.'
                        : null)
                    ->schema([
                        Select::make('role_id')
                            ->label('Role')
                            ->helperText('Tanpa role = tidak bisa masuk panel admin.')
                            ->options(fn (): array => Role::query()->orderBy('id')->get()
                                ->filter(fn (Role $role): bool => auth()->user()->canAssignRole($role))
                                ->mapWithKeys(fn (Role $role): array => [$role->id => $role->name])
                                ->all())
                            ->placeholder('Tanpa role')
                            ->rule(fn (?User $record) => GrantRules::role($record))
                            ->disabled($self),
                        CheckboxList::make('permissions')
                            ->label('Hak akses tambahan')
                            ->helperText('Di luar hak akses role-nya. Hanya hak akses yang Anda miliki sendiri yang bisa dicentang.')
                            ->options(fn (): array => GrantRules::grantableOptions())
                            ->descriptions(Permission::descriptions())
                            ->rule(fn (): \Closure => GrantRules::permissions())
                            ->default([])
                            ->columns(2)
                            ->disabled($self),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->placeholder('Tanpa role'),
                TextColumn::make('permissions')
                    ->label('Hak akses tambahan')
                    ->formatStateUsing(fn (string $state): string => Permission::tryFrom($state)?->label() ?? $state)
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('j M Y')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
