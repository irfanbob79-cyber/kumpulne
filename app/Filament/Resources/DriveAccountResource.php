<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriveAccountResource\Pages;
use App\Models\DriveAccount;
use App\Services\GoogleDriveService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DriveAccountResource extends Resource
{
    protected static ?string $model = DriveAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud';
    protected static ?string $navigationLabel = 'Akun Google Drive';
    protected static ?string $modelLabel = 'Akun Google Drive';
    protected static ?string $pluralModelLabel = 'Akun Google Drive';
    protected static ?string $navigationGroup = 'Integrasi Penyimpanan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->description('Konfigurasi identitas akun Google Drive')
                    ->schema([
                        Forms\Components\TextInput::make('account_name')
                            ->label('Nama Akun (Label)')
                            ->required()
                            ->placeholder('contoh: Google Drive Utama Sekolah')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('google_email')
                            ->label('Email Google Terhubung')
                            ->disabled()
                            ->placeholder('Otomatis terisi saat OAuth berhasil'),
                        Forms\Components\TextInput::make('root_folder_id')
                            ->label('ID Folder Root (Opsional)')
                            ->helperText('Biarkan kosong untuk otomatis membuat folder "Kumpulne" di root Google Drive')
                            ->placeholder('contoh: 1a2b3c4d5e...'),
                        Forms\Components\TextInput::make('priority')
                            ->label('Prioritas Penggunaan')
                            ->helperText('Urutan prioritas penggunaan akun (1 = paling utama)')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Jadikan Akun Aktif')
                            ->helperText('Sistem akan memprioritaskan akun ini untuk pengumpulan tugas baru')
                            ->default(true),
                        Forms\Components\Toggle::make('is_full')
                            ->label('Status Kuota Penuh')
                            ->helperText('Akan otomatis aktif jika Google Drive mengembalikan error kuota penuh')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Kredensial OAuth Khusus (Opsional)')
                    ->description('Hanya diisi jika akun ini menggunakan Client ID / Secret yang berbeda dari file .env sistem')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('client_id')
                            ->label('Client ID Khusus')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('client_secret')
                            ->label('Client Secret Khusus')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Nama Akun')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('google_email')
                    ->label('Email Google')
                    ->searchable()
                    ->default('Belum Terhubung')
                    ->badge()
                    ->color(fn ($state) => $state === 'Belum Terhubung' ? 'warning' : 'success'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->afterStateUpdated(function ($record, $state) {
                        if ($state) {
                            // Nonaktifkan akun lain agar hanya 1 yang aktif utama
                            DriveAccount::where('id', '!=', $record->id)->update(['is_active' => false]);
                        }
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('quota')
                    ->label('Kapasitas Terpakai')
                    ->state(function (DriveAccount $record) {
                        if (!$record->storage_limit_bytes) {
                            return 'Belum dicek';
                        }
                        $usedGB = round($record->storage_used_bytes / (1024 * 1024 * 1024), 2);
                        $limitGB = round($record->storage_limit_bytes / (1024 * 1024 * 1024), 2);
                        return "{$usedGB} GB / {$limitGB} GB";
                    })
                    ->badge()
                    ->color(function (DriveAccount $record) {
                        if (!$record->storage_limit_bytes) return 'gray';
                        $percentage = ($record->storage_used_bytes / $record->storage_limit_bytes) * 100;
                        if ($percentage >= 90) return 'danger';
                        if ($percentage >= 70) return 'warning';
                        return 'info';
                    }),
                Tables\Columns\IconColumn::make('is_full')
                    ->label('Penuh')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('connect_oauth')
                    ->label('Hubungkan Google')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('success')
                    ->url(fn (DriveAccount $record) => url("/admin/google-drive/auth/{$record->id}"))
                    ->openUrlInNewTab(false),
                Tables\Actions\Action::make('check_quota')
                    ->label('Cek Kuota')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (DriveAccount $record, GoogleDriveService $driveService) {
                        try {
                            $res = $driveService->updateQuota($record);
                            $usedGB = round($res['used'] / (1024 * 1024 * 1024), 2);
                            $limitGB = round($res['limit'] / (1024 * 1024 * 1024), 2);
                            Notification::make()
                                ->title('Kuota Berhasil Diperbarui')
                                ->body("Akun: {$record->google_email}\nTerpakai: {$usedGB} GB dari {$limitGB} GB.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Cek Kuota')
                                ->body('Pastikan akun sudah terhubung via OAuth Google: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListDriveAccounts::route('/'),
            'create' => Pages\CreateDriveAccount::route('/create'),
            'edit' => Pages\EditDriveAccount::route('/{record}/edit'),
        ];
    }
}
