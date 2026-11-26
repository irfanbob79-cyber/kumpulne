<?php

namespace App\Filament\Widgets;

use App\Models\Submission;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSubmissionsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Pengumpulan Tugas Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Submission::query()->with(['classroom', 'assignment'])->latest('submitted_at')->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('receipt_code')
                    ->label('Kode Resi')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('student_name')
                    ->label('Nama Siswa')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('assignment.title')
                    ->label('Tugas')
                    ->limit(35),
                Tables\Columns\TextColumn::make('file_count')
                    ->label('File')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('is_late')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? 'Terlambat' : 'Tepat Waktu'),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Waktu Kirim')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('open_drive')
                    ->label('Buka File')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('success')
                    ->visible(fn (Submission $record) => !empty($record->gdrive_web_view_link))
                    ->url(fn (Submission $record) => $record->gdrive_web_view_link)
                    ->openUrlInNewTab(),
            ])
            ->paginated(false);
    }
}
