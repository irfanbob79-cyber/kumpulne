<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Folder Tugas';
    protected static ?string $modelLabel = 'Tugas Siswa';
    protected static ?string $pluralModelLabel = 'Folder Tugas';
    protected static ?string $navigationGroup = 'Kegiatan Belajar';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tugas')
                    ->description('Detail tugas yang akan dikumpulkan siswa')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Tugas')
                            ->placeholder('contoh: Tugas 01 - Desain Kemasan Kopi')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation === 'create' || empty($set('slug'))) {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug Tautan Publik')
                            ->helperText('Akan menjadi URL pengumpulan langsung: /kumpul/{slug}')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('classroom_id')
                            ->label('Target Kelas')
                            ->relationship('classroom', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Semua Kelas (Terbuka)')
                            ->helperText('Pilih kelas spesifik atau kosongkan jika berlaku untuk semua kelas'),
                        Forms\Components\Textarea::make('description')
                            ->label('Instruksi / Deskripsi Pengerjaan')
                            ->placeholder('Tuliskan petunjuk pengerjaan tugas untuk siswa...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Ketentuan Unggahan & Batas Waktu')
                    ->schema([
                        Forms\Components\TagsInput::make('allowed_extensions')
                            ->label('Ekstensi File yang Diizinkan')
                            ->placeholder('Tambah ekstensi lalu enter (misal: pdf, jpg, zip)')
                            ->default(['pdf', 'jpg', 'png', 'zip', 'psd', 'ai', 'cdr'])
                            ->helperText('Contoh: pdf, jpg, png, zip, cdr, psd, ai. Kosongkan jika menerima semua ekstensi dokumen umum.')
                            ->separator(','),
                        Forms\Components\TextInput::make('max_file_size_mb')
                            ->label('Batas Maksimal Ukuran File (MB)')
                            ->numeric()
                            ->default(100)
                            ->suffix('MB')
                            ->required(),
                        Forms\Components\DateTimePicker::make('deadline')
                            ->label('Batas Waktu Pengumpulan (Deadline)')
                            ->native(false)
                            ->helperText('Siswa yang mengumpulkan melewati waktu ini akan ditandai terlambat'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Buka Pengumpulan (Status Aktif)')
                            ->helperText('Jika dinonaktifkan, siswa tidak dapat mengunggah file pada tugas ini')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Tugas')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Assignment $record) => $record->subject?->name ?? '-'),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->default('Semua Kelas')
                    ->badge()
                    ->color(fn ($state) => $state === 'Semua Kelas' ? 'gray' : 'primary'),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Terkumpul')
                    ->counts('submissions')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('Deadline')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->badge()
                    ->color(function (Assignment $record) {
                        if (!$record->deadline) return 'gray';
                        return $record->isExpired() ? 'danger' : 'info';
                    }),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name'),
                Tables\Filters\SelectFilter::make('classroom')
                    ->label('Kelas')
                    ->relationship('classroom', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\Action::make('qr_code')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->modalHeading(fn (Assignment $record) => "QR Code: {$record->title}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (Assignment $record) => view('filament.modals.qr-code', [
                        'assignment' => $record,
                        'url' => url("/kumpul/{$record->slug}"),
                    ])),

                Tables\Actions\Action::make('copy_link')
                    ->label('Salin Link')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->action(function (Assignment $record) {
                        $url = url("/kumpul/{$record->slug}");
                        Notification::make()
                            ->title('Tautan Siap Dibagikan!')
                            ->body("Tautan siswa: {$url}")
                            ->success()
                            ->send();
                    })
                    ->extraAttributes(fn (Assignment $record) => [
                        'onclick' => "navigator.clipboard.writeText('" . url("/kumpul/{$record->slug}") . "')",
                    ]),

                Tables\Actions\Action::make('view_public')
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Assignment $record) => url("/kumpul/{$record->slug}"))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }
}
