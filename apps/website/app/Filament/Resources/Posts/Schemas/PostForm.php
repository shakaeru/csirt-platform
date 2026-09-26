<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Models\Category;
use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make([
                        TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            // Slug otomatis dari judul hanya saat membuat — mengubah slug tulisan yang
                            // sudah terbit memutus tautan yang sudah dibagikan.
                            ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create'
                                ? $set('slug', Str::slug((string) $state))
                                : null),
                        TextInput::make('slug')
                            ->label('Slug (alamat)')
                            ->prefix('/berita/')
                            ->helperText('Otomatis dari judul. Mengubahnya setelah terbit memutus tautan lama.')
                            ->required()
                            ->maxLength(220)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        Textarea::make('excerpt')
                            ->label('Ringkasan')
                            ->helperText('Opsional — tampil di kartu daftar dan hasil pencarian. Kosong = diambil dari awal isi.')
                            ->rows(3)
                            ->maxLength(300),
                        RichEditor::make('content')
                            ->label('Isi')
                            ->required()
                            // Sisip gambar dimatikan: tanpa FileAttachmentProvider, Filament tidak
                            // membersihkan file lampiran yang dihapus dari isi/tulisan — file tertinggal
                            // di disk publik. Gambar memakai sampul; lihat CLAUDE.md aplikasi.
                            ->fileAttachments(false),
                    ]),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Publikasi')->schema([
                        DateTimePicker::make('published_at')
                            ->label('Terbit pada (WIB)')
                            ->helperText('Kosong = draft (tidak tampil). Masa depan = terjadwal, tampil otomatis saat waktunya tiba.')
                            ->seconds(false),
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name', fn ($query) => $query->orderBy('sort_order'))
                            ->default(fn (): ?int => Category::query()->where('slug', 'berita')->value('id'))
                            ->required(),
                        Select::make('tags')
                            ->label('Tag')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nama tag')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique('tags', 'name'),
                            ]),
                    ]),
                    Section::make('Gambar sampul')->schema([
                        FileUpload::make('cover_path')
                            ->hiddenLabel()
                            ->helperText('Opsional. JPG, PNG, atau WebP — otomatis dipotong 16:9 dan diperkecil ke 1200×675 di browser.')
                            ->image()
                            // Tanpa SVG: SVG bisa memuat script dan dilayani dari domain yang sama.
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk(Post::COVER_DISK)
                            ->directory('berita')
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
