<?php

namespace App\Filament\Resources\Divisions;

use App\Filament\Resources\Divisions\Pages\ManageDivisions;
use App\Models\Division;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DivisionResource extends Resource
{
    protected static ?string $model = Division::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Struktur Organisasi';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'divisi';

    protected static ?string $pluralModelLabel = 'Divisi';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama divisi')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->helperText('Opsional — tampil di bawah nama divisi di halaman Struktur Organisasi.')
                    ->rows(3),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->helperText('Angka kecil tampil lebih dulu.')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Divisi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(60)
                    ->placeholder('—'),
                TextColumn::make('members_count')
                    ->label('Jumlah pengurus')
                    ->counts('members'),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                // Divisi yang masih punya anggota tidak bisa dihapus (FK restrict) — sembunyikan tombolnya.
                DeleteAction::make()
                    ->hidden(fn (Division $record): bool => $record->members()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDivisions::route('/'),
        ];
    }
}
