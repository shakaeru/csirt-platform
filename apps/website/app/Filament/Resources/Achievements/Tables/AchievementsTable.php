<?php

namespace App\Filament\Resources\Achievements\Tables;

use App\Enums\AchievementCategory;
use App\Enums\AchievementLevel;
use App\Enums\PublicationStatus;
use App\Models\Achievement;
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

class AchievementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->disk(Achievement::PHOTO_DISK),
                TextColumn::make('competition')
                    ->label('Kompetisi')
                    ->description(fn (Achievement $record): string => $record->result)
                    ->searchable(['competition', 'result', 'team_name', 'members'])
                    ->sortable()
                    ->limit(60),
                TextColumn::make('category')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('level')
                    ->label('Tingkat')
                    ->badge(),
                TextColumn::make('achieved_on')
                    ->label('Tanggal')
                    ->date('j M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (Achievement $record): PublicationStatus => $record->status())
                    ->badge(),
            ])
            ->defaultSort('achieved_on', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Jenis')
                    ->options(AchievementCategory::class),
                SelectFilter::make('level')
                    ->label('Tingkat')
                    ->options(AchievementLevel::class),
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
                    ->url(fn (Achievement $record): string => route('prestasi').'#prestasi-'.$record->getKey())
                    ->openUrlInNewTab()
                    ->visible(fn (Achievement $record): bool => $record->isPublished()),
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
