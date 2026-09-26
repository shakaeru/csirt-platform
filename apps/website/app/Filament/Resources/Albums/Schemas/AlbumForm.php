<?php

namespace App\Filament\Resources\Albums\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AlbumForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Callout::make('Foto diunggah setelah album disimpan')
                        ->description('Simpan dulu judul dan tanggalnya; bagian "Foto" muncul di halaman berikutnya.')
                        ->info()
                        ->visibleOn('create'),
                    Section::make([
                        TextInput::make('title')
                            ->label('Judul album')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            // Sama dengan tulisan: slug otomatis hanya saat membuat.
                            ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create'
                                ? $set('slug', Str::slug((string) $state))
                                : null),
                        TextInput::make('slug')
                            ->label('Slug (alamat)')
                            ->prefix('/galeri/')
                            ->helperText('Otomatis dari judul. Mengubahnya setelah terbit memutus tautan lama.')
                            ->required()
                            ->maxLength(220)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->helperText('Opsional, teks biasa.')
                            ->rows(3)
                            ->maxLength(1000),
                        Select::make('post_id')
                            ->label('Tulisan terkait')
                            ->helperText('Opsional. Album tampil di halaman tulisan itu, dan album menautkan balik ke tulisannya — masing-masing hanya setelah terbit.')
                            ->relationship('post', 'title', fn ($query) => $query->latest())
                            ->searchable()
                            ->preload(),
                    ]),
                ])->columnSpan(2),

                Section::make('Publikasi')->schema([
                    DatePicker::make('event_date')
                        ->label('Tanggal kegiatan')
                        ->helperText('Urutan album di halaman galeri: kegiatan terbaru dulu.')
                        ->default(now())
                        ->required(),
                    DateTimePicker::make('published_at')
                        ->label('Terbit pada (WIB)')
                        ->helperText('Kosong = draft (tidak tampil) — unggah foto dulu, baru isi. Masa depan = terjadwal.')
                        ->seconds(false),
                ])->columnSpan(1),
            ]);
    }
}
