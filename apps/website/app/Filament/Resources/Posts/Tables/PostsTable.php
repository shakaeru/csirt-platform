<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostStatus;
use App\Models\Post;
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

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_path')
                    ->label('Sampul')
                    ->disk(Post::COVER_DISK),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (Post $record): PostStatus => $record->status())
                    ->badge(),
                TextColumn::make('published_at')
                    ->label('Terbit pada')
                    ->dateTime('j M Y, H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PostStatus::class)
                    ->query(fn (Builder $query, array $data): Builder => match (PostStatus::tryFrom((string) ($data['value'] ?? ''))) {
                        PostStatus::Draft => $query->whereNull('published_at'),
                        PostStatus::Scheduled => $query->where('published_at', '>', now()),
                        PostStatus::Published => $query->where('published_at', '<=', now()),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Post $record): string => route('berita.show', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Post $record): bool => $record->status() === PostStatus::Published),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
