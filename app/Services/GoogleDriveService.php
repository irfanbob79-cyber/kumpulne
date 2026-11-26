<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\DriveAccount;
use App\Models\Subject;
use Exception;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Oauth2 as GoogleOauth2;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoogleDriveService
{
    protected ?GoogleClient $client = null;
    protected ?GoogleDrive $driveService = null;
    protected ?DriveAccount $activeAccount = null;

    public function __construct()
    {
        $this->activeAccount = $this->getActiveAccount();
    }

    public function setAccount(DriveAccount $account): self
    {
        $this->activeAccount = $account;
        return $this;
    }

    public function getActiveAccount(): ?DriveAccount
    {
        return DriveAccount::where('is_active', true)
            ->where('is_full', false)
            ->orderBy('priority', 'asc')
            ->first();
    }

    public function getGoogleClient(?DriveAccount $account = null): GoogleClient
    {
        $account = $account ?: $this->activeAccount;
        $client = new GoogleClient();

        $clientId = $account?->client_id ?: config('services.google.client_id');
        $clientSecret = $account?->client_secret ?: config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri');

        if ($clientId) {
            $client->setClientId($clientId);
        }
        if ($clientSecret) {
            $client->setClientSecret($clientSecret);
        }
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->addScope([
            GoogleDrive::DRIVE,
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ]);

        if ($account && $account->refresh_token) {
            $tokenData = $client->fetchAccessTokenWithRefreshToken($account->refresh_token);
            if (isset($tokenData['error'])) {
                Log::warning("Gagal refresh token Google Drive untuk akun {$account->account_name}: " . json_encode($tokenData));
            }
        }

        return $client;
    }

    public function getAuthUrl(DriveAccount $account): string
    {
        $client = $this->getGoogleClient($account);
        $client->setState((string) $account->id);
        return $client->createAuthUrl();
    }

    public function handleAuthCode(string $code, DriveAccount $account): DriveAccount
    {
        $client = $this->getGoogleClient($account);
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new Exception("Error OAuth Google: " . ($token['error_description'] ?? $token['error']));
        }

        if (!empty($token['refresh_token'])) {
            $account->refresh_token = $token['refresh_token'];
        }

        // Ambil info email pengguna Google
        try {
            $oauth2 = new GoogleOauth2($client);
            $userInfo = $oauth2->userinfo->get();
            if ($userInfo && $userInfo->getEmail()) {
                $account->google_email = $userInfo->getEmail();
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal mengambil info userinfo: " . $e->getMessage());
        }

        $account->is_active = true;
        $account->is_full = false;
        $account->save();

        // Update kuota
        try {
            $this->updateQuota($account);
        } catch (\Throwable $e) {
            Log::warning("Gagal cek kuota setelah OAuth: " . $e->getMessage());
        }

        return $account;
    }

    public function updateQuota(DriveAccount $account): array
    {
        if (!$account->refresh_token) {
            return [
                'used' => $account->storage_used_bytes,
                'limit' => $account->storage_limit_bytes,
                'email' => $account->google_email,
            ];
        }

        $client = $this->getGoogleClient($account);
        $service = new GoogleDrive($client);
        $about = $service->about->get(['fields' => 'storageQuota,user']);

        $quota = $about->getStorageQuota();
        $user = $about->getUser();

        if ($user && $user->getEmailAddress()) {
            $account->google_email = $user->getEmailAddress();
        }

        if ($quota) {
            $account->storage_used_bytes = (int) $quota->getUsage();
            $account->storage_limit_bytes = (int) $quota->getLimit();
            if ($account->storage_limit_bytes > 0 && $account->storage_used_bytes >= $account->storage_limit_bytes) {
                $account->is_full = true;
            }
        }

        $account->save();

        return [
            'used' => $account->storage_used_bytes,
            'limit' => $account->storage_limit_bytes,
            'email' => $account->google_email,
        ];
    }

    public function isConnected(?DriveAccount $account = null): bool
    {
        $acc = $account ?: ($this->activeAccount ?: $this->getActiveAccount());
        return $acc && $acc->exists && !empty($acc->refresh_token);
    }

    /**
     * Cari atau buat folder di Google Drive
     */
    public function findOrCreateFolder(string $folderName, ?string $parentId = null, ?GoogleDrive $service = null): string
    {
        if (!$this->isConnected()) {
            return 'local_folder_' . md5($folderName);
        }

        $service = $service ?: new GoogleDrive($this->getGoogleClient());
        $cleanName = $this->sanitizeName($folderName);

        $query = "name = '" . str_replace("'", "\\'", $cleanName) . "' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
        if ($parentId) {
            $query .= " and '{$parentId}' in parents";
        }

        $response = $service->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
            'pageSize' => 1,
        ]);

        if (count($response->getFiles()) > 0) {
            return $response->getFiles()[0]->getId();
        }

        $folderMetadata = new DriveFile([
            'name' => $cleanName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        if ($parentId) {
            $folderMetadata->setParents([$parentId]);
        }

        $folder = $service->files->create($folderMetadata, ['fields' => 'id']);
        return $folder->getId();
    }

    /**
     * Susun hierarki folder: [Root] / [Kelas] / [Mata Pelajaran] / [Judul Tugas]
     */
    public function getAssignmentFolderId(Classroom $classroom, Subject $subject, Assignment $assignment, ?GoogleDrive $service = null): string
    {
        if (!$this->isConnected()) {
            return 'local_assignment_' . $assignment->id;
        }

        $account = $this->activeAccount;
        $service = $service ?: new GoogleDrive($this->getGoogleClient($account));
        $rootId = $account?->root_folder_id ?: null;

        // 1. Root Kumpulne (jika rootId tidak diset)
        if (!$rootId) {
            $rootId = $this->findOrCreateFolder('Kumpulne', null, $service);
        }

        // 2. Folder Kelas
        $classFolderId = $this->findOrCreateFolder($classroom->name, $rootId, $service);

        // 3. Folder Mapel
        $subjectFolderId = $this->findOrCreateFolder($subject->name, $classFolderId, $service);

        // 4. Folder Judul Tugas
        return $this->findOrCreateFolder($assignment->title, $subjectFolderId, $service);
    }

    /**
     * Unggah 1 file dengan Chunked/Resumable Upload (5MB per chunk)
     */
    public function uploadFile(
        string $localFilePath,
        string $targetFilename,
        string $parentFolderId,
        ?string $mimeType = null,
        int $retryAttempt = 0
    ): array {
        // Mode fallback jika Google Drive belum terkoneksi refresh_token (misal saat dev tanpa Google API)
        if (!$this->isConnected()) {
            return $this->uploadLocalFallback($localFilePath, $targetFilename, $parentFolderId);
        }

        $chunkSize = (int) config('services.google.chunk_size_mb', 5) * 1024 * 1024;
        $mimeType = $mimeType ?: (File::mimeType($localFilePath) ?: 'application/octet-stream');

        try {
            $client = $this->getGoogleClient();
            $service = new GoogleDrive($client);

            $fileMetadata = new DriveFile([
                'name' => $this->sanitizeName($targetFilename),
                'parents' => [$parentFolderId],
            ]);

            // Resumable upload via setDefer
            $client->setDefer(true);
            $request = $service->files->create($fileMetadata, ['fields' => 'id, webViewLink']);

            $media = new \Google\Http\MediaFileUpload(
                $client,
                $request,
                $mimeType,
                null,
                true,
                $chunkSize
            );
            $media->setFileSize(filesize($localFilePath));

            $status = false;
            $handle = fopen($localFilePath, 'rb');
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                $status = $media->nextChunk($chunk);
            }
            fclose($handle);
            $client->setDefer(false);

            if ($status !== false && $status instanceof DriveFile) {
                return [
                    'file_id' => $status->getId(),
                    'web_view_link' => $status->getWebViewLink(),
                    'drive_account_id' => $this->activeAccount?->id,
                ];
            }

            throw new Exception("Gagal menyelesaikan upload ke Google Drive.");
        } catch (\Throwable $e) {
            Log::error("Upload GDrive error: " . $e->getMessage());

            // Deteksi Kuota Penuh (Auto-Failover)
            if ($this->isQuotaExceededException($e) && $retryAttempt < 3) {
                Log::warning("Google Drive kuota penuh pada akun {$this->activeAccount?->account_name}. Mencoba auto-failover...");
                $this->failoverToNextAccount();
                return $this->uploadFile($localFilePath, $targetFilename, $parentFolderId, $mimeType, $retryAttempt + 1);
            }

            throw $e;
        }
    }

    /**
     * Buat subfolder untuk siswa jika upload batch / multi-file / folder utuh
     */
    public function createStudentFolder(string $studentFolderName, string $assignmentFolderId): string
    {
        if (!$this->isConnected()) {
            return 'local_folder_' . md5($studentFolderName);
        }

        $service = new GoogleDrive($this->getGoogleClient());
        return $this->findOrCreateFolder($studentFolderName, $assignmentFolderId, $service);
    }

    /**
     * Auto-failover ke akun cadangan berikutnya jika kuota penuh
     */
    public function failoverToNextAccount(): ?DriveAccount
    {
        if ($this->activeAccount) {
            $this->activeAccount->is_full = true;
            $this->activeAccount->is_active = false;
            $this->activeAccount->save();
        }

        $nextAccount = DriveAccount::where('is_full', false)
            ->whereNotNull('refresh_token')
            ->orderBy('priority', 'asc')
            ->first();

        if ($nextAccount) {
            $nextAccount->is_active = true;
            $nextAccount->save();
            $this->activeAccount = $nextAccount;
            return $nextAccount;
        }

        Log::critical("Semua akun Google Drive penuh atau tidak tersedia!");
        return null;
    }

    protected function isQuotaExceededException(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'storagequotaexceeded')
            || str_contains($message, 'quota exceeded')
            || str_contains($message, 'userstoragequotaexceeded')
            || $e->getCode() === 507;
    }

    public function sanitizeName(string $name): string
    {
        // Hapus karakter terlarang: \ / : * ? " < > |
        $cleaned = preg_replace('/[\\\\\\/:\*\?"<>\|]/', '-', $name);
        // Rapikan spasi atau strip berulang
        $cleaned = preg_replace('/[-\s]+/', ' ', $cleaned);
        return trim($cleaned);
    }

    /**
     * Fallback penyimpanan lokal jika Google Drive belum dihubungkan oleh guru
     */
    protected function uploadLocalFallback(string $localFilePath, string $targetFilename, string $parentFolderId): array
    {
        $safeName = $this->sanitizeName($targetFilename);
        $folderPath = 'public/submissions/' . date('Y-m');
        $destPath = storage_path('app/' . $folderPath);

        if (!File::isDirectory($destPath)) {
            File::makeDirectory($destPath, 0755, true);
        }

        $uniqueFilename = time() . '_' . Str::random(6) . '_' . $safeName;
        File::copy($localFilePath, $destPath . '/' . $uniqueFilename);

        return [
            'file_id' => 'local_' . uniqid(),
            'web_view_link' => url('/storage/submissions/' . date('Y-m') . '/' . $uniqueFilename),
            'drive_account_id' => $this->activeAccount?->id,
        ];
    }
}
