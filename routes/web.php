<?php

use App\Http\Controllers\GoogleDriveOAuthController;
use App\Http\Controllers\StudentPortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Kumpulne Portal Siswa & Google OAuth
|--------------------------------------------------------------------------
*/

// Portal Publik Siswa (Tanpa Login)
Route::get('/', [StudentPortalController::class, 'index'])->name('portal.index');
Route::get('/kumpul/{slug}', [StudentPortalController::class, 'show'])->name('portal.show');
Route::post('/kumpul/{slug}', [StudentPortalController::class, 'submit'])
    ->middleware('throttle:30,1')
    ->name('portal.submit');
Route::get('/resi/{receipt_code}', [StudentPortalController::class, 'checkReceipt'])->name('portal.receipt');

// Google Drive OAuth Routes
Route::get('/admin/google-drive/auth/{account}', [GoogleDriveOAuthController::class, 'redirect'])->name('google-drive.auth');
Route::get('/admin/google-drive/callback', [GoogleDriveOAuthController::class, 'callback'])->name('google-drive.callback');
