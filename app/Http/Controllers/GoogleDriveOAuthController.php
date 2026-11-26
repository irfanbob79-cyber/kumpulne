<?php

namespace App\Http\Controllers;

use App\Models\DriveAccount;
use App\Services\GoogleDriveService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleDriveOAuthController extends Controller
{
    public function redirect(DriveAccount $account, GoogleDriveService $driveService)
    {
        try {
            $authUrl = $driveService->getAuthUrl($account);
            return redirect()->away($authUrl);
        } catch (\Throwable $e) {
            Log::error("Gagal inisialisasi OAuth Google Drive: " . $e->getMessage());
            return redirect('/admin/drive-accounts')->with('error', 'Gagal membuat URL OAuth: ' . $e->getMessage());
        }
    }

    public function callback(Request $request, GoogleDriveService $driveService)
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $error = $request->input('error');

        if ($error) {
            return redirect('/admin/drive-accounts')->with('error', 'Persetujuan Google ditolak: ' . $error);
        }

        if (!$code || !$state) {
            return redirect('/admin/drive-accounts')->with('error', 'Parameter OAuth tidak lengkap.');
        }

        $account = DriveAccount::find($state);
        if (!$account) {
            return redirect('/admin/drive-accounts')->with('error', 'Akun Drive tidak ditemukan.');
        }

        try {
            $driveService->handleAuthCode($code, $account);

            return redirect('/admin/drive-accounts')->with('success', "Akun Google Drive '{$account->account_name}' berhasil terhubung ({$account->google_email})!");
        } catch (\Throwable $e) {
            Log::error("Gagal callback OAuth Google Drive: " . $e->getMessage());
            return redirect('/admin/drive-accounts')->with('error', 'Gagal menghubungkan akun Google: ' . $e->getMessage());
        }
    }
}
