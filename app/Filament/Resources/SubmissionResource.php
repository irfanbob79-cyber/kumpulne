<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionResource\Pages;
use App\Models\Submission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Rekap Pengumpulan';
    protected static ?string $modelLabel = 'Pengumpulan Tugas';
    protected static ?string $pluralModelLabel = 'Rekap Pengumpulan Siswa';
    protected static ?string $navigationGroup = 'Kegiatan Belajar';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Pengumpulan')
                    ->schema([
                        Forms\Components\TextInput::make('receipt_code')
                            ->label('Nomor Resi')
                            ->disabled(),
                        Forms\Components\TextInput::make('student_name')
                            ->label('Nama Siswa')
                            ->required(),
                        Forms\Components\Select::make('classroom_id')
                            ->label('Kelas')
                            ->relationship('classroom', 'name')
                            ->required(),
                        Forms\Components\Select::make('assignment_id')
                            ->label('Tugas')
                            ->relationship('assignment', 'title')
                            ->required(),
                        Forms\Components\TextInput::make('upload_type')
                            ->label('Tipe Unggahan')
                            ->disabled(),
                        Forms\Components\TextInput::make('file_count')
                            ->label('Jumlah File')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('formatted_size')
                            ->label('Ukuran Total')
                            ->formatStateUsing(fn ($record) => $record ? round($record->total_size_bytes / 1024 / 1024, 2) . ' MB' : '0 MB')
                            ->disabled(),
                        Forms\Components\Toggle::make('is_late')
                            ->label('Keterlambatan (Terlambat)'),
                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Waktu Dikumpulkan')
                            ->disabled(),
                        Forms\Components\TextInput::make('gdrive_web_view_link')
                            ->label('Link File di Google Drive')
                            ->columnSpanFull()
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pengumpulan Siswa')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Infolists\Components\TextEntry::make('receipt_code')
                            ->label('Kode Resi')
                            ->fontFamily('mono')
                            ->weight('bold')
                            ->badge()
                            ->color('primary')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('is_late')
                            ->label('Status Ketepatan')
                            ->badge()
                            ->color(fn ($state) => $state ? 'danger' : 'success')
                            ->formatStateUsing(fn ($state) => $state ? 'Terlambat' : 'Tepat Waktu'),
                        Infolists\Components\TextEntry::make('student_name')
                            ->label('Nama Siswa')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('classroom.name')
                            ->label('Kelas')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('assignment.title')
                            ->label('Tugas')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('upload_type')
                            ->label('Tipe Unggahan')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state === 'single_file' ? '1 File Tunggal' : 'Folder / Multi-File'),
                        Infolists\Components\TextEntry::make('total_size_bytes')
                            ->label('Total Ukuran')
                            ->formatStateUsing(fn ($state) => $state >= 1024 * 1024 ? round($state / 1024 / 1024, 2) . ' MB' : round($state / 1024, 1) . ' KB'),
                        Infolists\Components\TextEntry::make('submitted_at')
                            ->label('Waktu Dikumpulkan')
                            ->dateTime('d M Y, H:i:s'),
                        Infolists\Components\TextEntry::make('driveAccount.account_name')
                            ->label('Akun Penyimpanan')
                            ->default('Penyimpanan Lokal (Fallback)')
                            ->badge()
                            ->color(fn ($record) => str_starts_with((string) $record->gdrive_target_id, 'local_') ? 'warning' : 'success'),
                        Infolists\Components\TextEntry::make('gdrive_web_view_link')
                            ->label('Tautan File')
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->color('primary')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Rincian File Terkirim')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('original_filename')
                                    ->label('Nama Asli')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('renamed_filename')
                                    ->label('Nama Tersimpan')
                                    ->fontFamily('mono'),
                                Infolists\Components\TextEntry::make('file_size_bytes')
                                    ->label('Ukuran')
                                    ->formatStateUsing(fn ($state) => $state >= 1024 * 1024 ? round($state / 1024 / 1024, 2) . ' MB' : round($state / 1024, 1) . ' KB'),
                                Infolists\Components\TextEntry::make('gdrive_web_view_link')
                                    ->label('Link File')
                                    ->formatStateUsing(fn () => 'Buka File ↗')
                                    ->url(fn ($state) => $state)
                                    ->openUrlInNewTab()
                                    ->color('success'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('receipt_code')
                    ->label('Kode Resi')
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('student_name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignment.title')
                    ->label('Tugas')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('upload_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn ($state) => $state === 'single_file' ? 'gray' : 'warning')
                    ->formatStateUsing(fn ($state) => $state === 'single_file' ? '1 File' : 'Folder / Multi'),
                Tables\Columns\TextColumn::make('file_count')
                    ->label('Files')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_size_bytes')
                    ->label('Ukuran')
                    ->formatStateUsing(function ($state) {
                        if ($state >= 1024 * 1024) {
                            return round($state / 1024 / 1024, 2) . ' MB';
                        }
                        return round($state / 1024, 1) . ' KB';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_late')
                    ->label('Ketepatan')
                    ->badge()
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? 'Terlambat' : 'Tepat Waktu')
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Waktu Kirim')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('assignment_id')
                    ->label('Filter Tugas')
                    ->relationship('assignment', 'title'),
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Filter Kelas')
                    ->relationship('classroom', 'name'),
                Tables\Filters\TernaryFilter::make('is_late')
                    ->label('Status Ketepatan Waktu')
                    ->placeholder('Semua')
                    ->trueLabel('Terlambat')
                    ->falseLabel('Tepat Waktu'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Export CSV / Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $submissions = Submission::with(['assignment', 'classroom', 'driveAccount'])
                            ->orderBy('submitted_at', 'desc')
                            ->get();

                        $headers = [
                            'Content-Type' => 'text/csv; charset=utf-8',
                            'Content-Disposition' => 'attachment; filename="rekap-pengumpulan-kumpulne-' . date('Ymd-His') . '.csv"',
                        ];

                        $callback = function () use ($submissions) {
                            $file = fopen('php://output', 'w');
                            // BOM UTF-8 for Excel compatibility
                            fputs($file, "\xEF\xBB\xBF");
                            fputcsv($file, [
                                'Kode Resi',
                                'Nama Siswa',
                                'Kelas',
                                'Judul Tugas',
                                'Tipe Unggah',
                                'Jumlah File',
                                'Total Ukuran (MB)',
                                'Status Ketepatan',
                                'Waktu Dikumpulkan',
                                'Link Google Drive',
                            ], ';');

                            foreach ($submissions as $sub) {
                                fputcsv($file, [
                                    $sub->receipt_code,
                                    $sub->student_name,
                                    $sub->classroom?->name ?? '-',
                                    $sub->assignment?->title ?? '-',
                                    $sub->upload_type === 'single_file' ? '1 File' : 'Folder / Multi File',
                                    $sub->file_count,
                                    round($sub->total_size_bytes / 1024 / 1024, 2),
                                    $sub->is_late ? 'Terlambat' : 'Tepat Waktu',
                                    $sub->submitted_at?->format('Y-m-d H:i:s'),
                                    $sub->gdrive_web_view_link,
                                ], ';');
                            }
                            fclose($file);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open_drive')
                    ->label('Buka di Drive')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('success')
                    ->visible(fn (Submission $record) => !empty($record->gdrive_web_view_link))
                    ->url(fn (Submission $record) => $record->gdrive_web_view_link)
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissions::route('/'),
            'view' => Pages\ViewSubmission::route('/{record}'),
        ];
    }
}
