<?php

namespace App\Filament\Resources\BoardPeriods;

use App\Filament\Resources\BoardPeriods\Pages\ManageBoardPeriods;
use App\Models\BoardPeriod;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BoardPeriodResource extends Resource
{
    protected static ?string $model = BoardPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Struktur Organisasi';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'periode';

    protected static ?string $pluralModelLabel = 'Periode';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama periode')
                    ->placeholder('2026/2027')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                DatePicker::make('starts_on')
                    ->label('Mulai'),
                DatePicker::make('ends_on')
                    ->label('Selesai')
                    ->afterOrEqual('starts_on'),
                Toggle::make('is_active')
                    ->label('Periode aktif (tampil di website)')
                    ->helperText('Hanya satu periode yang aktif. Mengaktifkan periode ini menonaktifkan periode lain.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Periode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('starts_on')
                    ->label('Mulai')
                    ->date()
                    ->placeholder('-'),
                TextColumn::make('ends_on')
                    ->label('Selesai')
                    ->date()
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('members_count')
                    ->label('Jumlah pengurus')
                    ->counts('members'),
            ])
            ->defaultSort('name', 'desc')
            ->recordActions([
                EditAction::make(),
                // Periode yang masih punya anggota tidak bisa dihapus (FK restrict) — sembunyikan tombolnya.
                DeleteAction::make()
                    ->hidden(fn (BoardPeriod $record): bool => $record->members()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBoardPeriods::route('/'),
        ];
    }
}
