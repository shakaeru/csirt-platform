<?php

namespace App\Filament\Resources\Albums\RelationManagers;

use App\Models\Album;
use App\Models\Photo;
use App\Support\Gallery\GalleryPhotoProcessor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Foto';

    /** Maksimal file per sekali unggah — pemrosesan GD berjalan di request yang sama. */
    public const MAX_FILES_PER_UPLOAD = 30;

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('foto')
            ->pluralModelLabel('Foto')
            ->description('Foto pertama menjadi sampul album. Ubah urutan lewat tombol urutkan, lalu seret.')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('thumb_path')
                    ->label('Foto')
                    ->disk(Album::DISK)
                    ->imageHeight(64),
                TextInputColumn::make('caption')
                    ->label('Keterangan')
                    ->placeholder('Opsional — tampil di bawah foto saat dibuka')
                    ->rules(['nullable', 'string', 'max:200']),
                TextColumn::make('dimensions')
                    ->label('Ukuran')
                    ->state(fn (Photo $record): string => "{$record->width}×{$record->height}")
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('Belum ada foto')
            ->emptyStateDescription('Klik "Unggah foto" untuk menambahkan foto ke album ini.')
            ->headerActions([
                $this->uploadAction(),
            ])
            ->recordActions([
                Action::make('buka')
                    ->label('Buka')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Photo $record): string => $record->url)
                    ->openUrlInNewTab(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function uploadAction(): Action
    {
        return Action::make('unggah')
            ->label('Unggah foto')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->modalHeading('Unggah foto')
            ->modalSubmitActionLabel('Unggah')
            ->schema([
                FileUpload::make('files')
                    ->hiddenLabel()
                    ->helperText('Maks. '.self::MAX_FILES_PER_UPLOAD.' foto sekali unggah. JPG, PNG, atau WebP. Diperkecil ke '
                        .GalleryPhotoProcessor::MAX_SIDE.' px di browser; di server metadata foto (termasuk lokasi GPS) dibuang.')
                    ->multiple()
                    ->image()
                    // Tanpa SVG: SVG bisa memuat script dan dilayani dari domain yang sama.
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    // Dicek terhadap file asli sebelum resize — lihat catatan di BoardMemberResource.
                    ->maxSize(10240)
                    ->maxFiles(self::MAX_FILES_PER_UPLOAD)
                    ->automaticallyResizeImagesMode('contain')
                    ->automaticallyResizeImagesToWidth((string) GalleryPhotoProcessor::MAX_SIDE)
                    ->automaticallyResizeImagesToHeight((string) GalleryPhotoProcessor::MAX_SIDE)
                    ->automaticallyUpscaleImagesWhenResizing(false)
                    // File tidak disimpan apa adanya: diproses GalleryPhotoProcessor di action().
                    ->storeFiles(false)
                    ->required(),
            ])
            ->action(function (array $data): void {
                /** @var Album $album */
                $album = $this->getOwnerRecord();
                $added = 0;
                $failures = [];

                foreach ($data['files'] as $file) {
                    // Hanya file yang baru diunggah lewat Livewire. Nilai string = path yang dikirim
                    // klien (bisa dimanipulasi), jadi tidak pernah dibaca sebagai file.
                    if (! $file instanceof TemporaryUploadedFile) {
                        continue;
                    }

                    try {
                        $album->addPhoto($file->getRealPath(), name: $file->getClientOriginalName());
                        $added++;
                    } catch (InvalidArgumentException $e) {
                        $failures[] = $e->getMessage();
                    } finally {
                        $file->delete();
                    }
                }

                Notification::make()
                    ->title("{$added} foto ditambahkan")
                    ->body($failures === [] ? null : 'Gagal: '.implode(' ', $failures))
                    ->status($failures === [] ? 'success' : 'warning')
                    ->persistent($failures !== [])
                    ->send();
            });
    }
}
