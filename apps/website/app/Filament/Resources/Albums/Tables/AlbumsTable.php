<?php

namespace App\Filament\Resources\Albums\Tables;

use App\Enums\PublicationStatus;
use App\Models\Album;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AlbumsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['cover', 'post'])->withCount('photos'))
            ->columns([
                ImageColumn::make('cover.thumb_path')
                    ->label('Sampul')
                    ->disk(Album::DISK),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('photos_count')
                    ->label('Foto')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (Album $record): PublicationStatus => $record->status())
                    ->badge(),
                TextColumn::make('event_date')
                    ->label('Tanggal kegiatan')
                    ->date('j M Y')
                    ->sortable(),
                TextColumn::make('post.title')
                    ->label('Tulisan terkait')
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Terbit pada')
                    ->dateTime('j M Y, H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PublicationStatus::class)
                    ->query(fn (Builder $query, array $data): Builder => ($status = PublicationStatus::tryFrom((string) ($data['value'] ?? '')))
                        ? $query->wherePublicationStatus($status)
                        : $query),
            ])
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Album $record): string => route('galeri.show', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Album $record): bool => $record->isPublished()),
                EditAction::make(),
                // Menghapus album ikut menghapus semua fotonya (baris dan file).
                DeleteAction::make()
                    ->modalDescription('Semua foto di album ini ikut terhapus permanen.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('Semua foto di album-album ini ikut terhapus permanen.'),
                ]),
            ]);
    }
}
