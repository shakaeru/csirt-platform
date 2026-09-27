<?php

namespace App\Filament\Resources\Achievements\Schemas;

use App\Enums\AchievementCategory;
use App\Enums\AchievementLevel;
use App\Models\Achievement;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AchievementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make([
                        TextInput::make('competition')
                            ->label('Nama kompetisi')
                            ->placeholder('mis. Cyber Jawara 2026')
                            ->required()
                            ->maxLength(200),
                        TextInput::make('result')
                            ->label('Capaian')
                            ->placeholder('mis. Juara 2, Finalis, Peringkat 12 dari 350 tim')
                            ->required()
                            ->maxLength(100),
                        Grid::make(2)->schema([
                            Select::make('category')
                                ->label('Jenis')
                                ->options(AchievementCategory::class)
                                ->default(AchievementCategory::Ctf)
                                ->required(),
                            Select::make('level')
                                ->label('Tingkat')
                                ->options(AchievementLevel::class)
                                ->default(AchievementLevel::Nasional)
                                ->required(),
                            TextInput::make('organizer')
                                ->label('Penyelenggara')
                                ->maxLength(150),
                            TextInput::make('team_name')
                                ->label('Nama tim')
                                ->maxLength(100),
                        ]),
                        Textarea::make('members')
                            ->label('Anggota')
                            ->helperText('Satu nama per baris. Tampil di halaman publik, jadi tulis hanya nama yang bersedia dicantumkan.')
                            ->rows(4)
                            ->maxLength(1000),
                        Textarea::make('description')
                            ->label('Keterangan')
                            ->helperText('Opsional, teks biasa.')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Publikasi')->schema([
                        DatePicker::make('achieved_on')
                            ->label('Tanggal')
                            ->helperText('Tanggal pengumuman atau pelaksanaan. Halaman Prestasi dikelompokkan per tahun.')
                            ->default(now())
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label('Terbit pada (WIB)')
                            ->helperText('Kosong = draft (tidak tampil).')
                            ->default(now())
                            ->seconds(false),
                        Select::make('post_id')
                            ->label('Tulisan terkait')
                            ->helperText('Opsional. Tautan "Baca beritanya" tampil bila tulisannya sudah terbit.')
                            ->relationship('post', 'title', fn ($query) => $query->latest())
                            ->searchable()
                            ->preload(),
                        TextInput::make('result_url')
                            ->label('Tautan hasil')
                            ->helperText('Opsional: scoreboard, CTFtime, atau pengumuman resmi.')
                            ->url()
                            // Hanya http/https — URL lain (mis. javascript:) tidak boleh jadi tautan di situs.
                            ->rule('url:http,https')
                            ->maxLength(500),
                    ]),
                    Section::make('Foto')->schema([
                        FileUpload::make('photo_path')
                            ->hiddenLabel()
                            ->helperText('Opsional: foto tim atau sertifikat. JPG, PNG, atau WebP. Otomatis dipotong 16:9 dan diperkecil ke 1200×675 di browser.')
                            ->image()
                            // Tanpa SVG: SVG bisa memuat script dan dilayani dari domain yang sama.
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk(Achievement::PHOTO_DISK)
                            ->directory('prestasi')
                            ->visibility('public')
                            // Dicek terhadap file asli sebelum resize — lihat catatan di BoardMemberResource.
                            ->maxSize(10240)
                            ->automaticallyResizeImagesMode('cover')
                            ->automaticallyResizeImagesToWidth('1200')
                            ->automaticallyResizeImagesToHeight('675'),
                    ]),
                ])->columnSpan(1),
            ]);
    }
}
