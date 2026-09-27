<?php

namespace App\Filament\Resources\BoardMembers;

use App\Enums\BoardSection;
use App\Filament\Resources\BoardMembers\Pages\ManageBoardMembers;
use App\Models\BoardMember;
use App\Models\BoardPeriod;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BoardMemberResource extends Resource
{
    protected static ?string $model = BoardMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Struktur Organisasi';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'anggota pengurus';

    protected static ?string $pluralModelLabel = 'Pengurus';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('board_period_id')
                    ->label('Periode')
                    ->relationship('period', 'name')
                    ->default(fn (): ?int => BoardPeriod::query()->active()->value('id'))
                    ->required(),
                Select::make('section')
                    ->label('Bagian')
                    ->options(BoardSection::class)
                    ->default(BoardSection::Divisi)
                    ->required()
                    ->live(),
                Select::make('division_id')
                    ->label('Divisi')
                    ->relationship('division', 'name')
                    ->visible(fn (Get $get): bool => self::isDivisi($get('section')))
                    ->required(fn (Get $get): bool => self::isDivisi($get('section'))),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('position')
                    ->label('Jabatan')
                    ->placeholder('mis. Ketua Umum, Kepala Divisi, Anggota')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('photo_path')
                    ->label('Foto')
                    ->helperText('Opsional. JPG, PNG, atau WebP. Otomatis dipotong persegi dan diperkecil ke 600×600 di browser sebelum diupload.')
                    ->image()
                    // Tanpa SVG: SVG bisa memuat script dan dilayani dari domain yang sama.
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk(BoardMember::PHOTO_DISK)
                    ->directory('pengurus')
                    ->visibility('public')
                    // Batas ukuran dicek terhadap file asli (sebelum resize), jadi longgar untuk foto
                    // kamera ponsel; yang terkirim ke server sudah kecil. Tetap di bawah batas upload
                    // Livewire (12 MB) dan Nginx (13m) — lihat docs/ARCHITECTURE.md § 4.
                    ->maxSize(10240)
                    ->automaticallyResizeImagesMode('cover')
                    ->automaticallyResizeImagesToWidth('600')
                    ->automaticallyResizeImagesToHeight('600'),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->helperText('Angka kecil tampil lebih dulu di dalam bagian/divisinya.')
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
                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->disk(BoardMember::PHOTO_DISK)
                    ->circular(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->label('Jabatan')
                    ->searchable(),
                TextColumn::make('section')
                    ->label('Bagian')
                    ->badge(),
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->placeholder('-'),
                TextColumn::make('period.name')
                    ->label('Periode')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('board_period_id')
                    ->label('Periode')
                    ->relationship('period', 'name')
                    ->default(BoardPeriod::query()->active()->value('id')),
                SelectFilter::make('section')
                    ->label('Bagian')
                    ->options(BoardSection::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBoardMembers::route('/'),
        ];
    }

    /** State Select bisa berupa instance enum atau nilai string-nya, tergantung asal datanya. */
    private static function isDivisi(mixed $state): bool
    {
        $section = $state instanceof BoardSection ? $state : BoardSection::tryFrom((string) $state);

        return $section === BoardSection::Divisi;
    }
}
