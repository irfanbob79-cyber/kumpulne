<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\DriveAccount;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\SubmissionItem;
use App\Services\GoogleDriveService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentPortalController extends Controller
{
    protected array $blacklistedExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'cmd', 'sh', 'bash',
        'vbs', 'msi', 'jsp', 'asp', 'aspx', 'cgi', 'pl', 'py', 'jar'
    ];

    /**
     * Halaman Beranda (Grid Kartu Tugas)
     */
    public function index(Request $request)
    {
        $classrooms = Classroom::where('is_active', true)->orderBy('name')->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();

        $assignments = Assignment::with(['subject', 'classroom'])
            ->where('is_active', true)
            ->orderBy('deadline', 'asc')
            ->get();

        $assignmentsJson = $assignments->map(function ($a) {
            return [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'description' => $a->description,
                'classroom_id' => $a->classroom_id,
                'classroom_name' => $a->classroom ? $a->classroom->name : 'Semua Kelas',
                'subject_id' => $a->subject_id,
                'subject_name' => $a->subject ? $a->subject->name : '-',
                'allowed_extensions' => $a->allowed_extensions,
                'max_file_size_mb' => $a->max_file_size_mb,
                'deadline' => $a->deadline ? $a->deadline->toISOString() : null,
                'deadline_formatted' => $a->deadline ? $a->deadline->isoFormat('D MMM Y, HH:mm') : null,
                'is_expired' => $a->isExpired(),
            ];
        });

        return view('portal.index', compact('classrooms', 'subjects', 'assignments', 'assignmentsJson'));
    }

    /**
     * Halaman Langsung Pengumpulan Tugas (/kumpul/{slug})
     */
    public function show(string $slug)
    {
        $assignment = Assignment::with(['subject', 'classroom'])
            ->where('slug', $slug)
            ->firstOrFail();

        $classrooms = Classroom::where('is_active', true)->orderBy('name')->get();

        return view('portal.show', compact('assignment', 'classrooms'));
    }

    /**
     * Proses Unggah File / Folder Siswa
     */
    public function submit(Request $request, string $slug, GoogleDriveService $driveService): JsonResponse
    {
        $assignment = Assignment::with(['subject', 'classroom'])
            ->where('slug', $slug)
            ->firstOrFail();

        if (!$assignment->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Pengumpulan tugas ini sudah ditutup oleh guru.',
            ], 422);
        }

        $validated = $request->validate([
            'student_name' => 'required|string|min:2|max:100',
            'classroom_id' => 'required|exists:classrooms,id',
            'files' => 'required|array|min:1',
            'files.*' => 'file',
            'relative_paths' => 'nullable|array',
        ]);

        $classroom = Classroom::findOrFail($validated['classroom_id']);

        // Jika tugas dikhususkan untuk 1 kelas tertentu, verifikasi
        if ($assignment->classroom_id && $assignment->classroom_id !== $classroom->id) {
            return response()->json([
                'success' => false,
                'message' => "Tugas ini dikhususkan hanya untuk kelas {$assignment->classroom->name}.",
            ], 422);
        }

        $uploadedFiles = $request->file('files');
        $relativePaths = $request->input('relative_paths', []);
        $allowedExtensions = $assignment->allowed_extensions ? array_map('strtolower', $assignment->allowed_extensions) : [];
        $maxSizeBytes = $assignment->max_file_size_mb * 1024 * 1024;

        $totalSizeBytes = 0;

        // Validasi ekstensi & ukuran tiap file
        foreach ($uploadedFiles as $index => $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            $origName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $totalSizeBytes += $fileSize;

            // Cek file berbahaya (Blacklist)
            if (in_array($ext, $this->blacklistedExtensions)) {
                return response()->json([
                    'success' => false,
                    'message' => "File '{$origName}' terdeteksi sebagai jenis file yang dilarang (.{$ext}).",
                ], 422);
            }

            // Cek whitelist jika tugas menetapkan ekstensi yang diizinkan
            if (!empty($allowedExtensions) && !in_array($ext, $allowedExtensions)) {
                $allowedList = implode(', ', $allowedExtensions);
                return response()->json([
                    'success' => false,
                    'message' => "Format file '{$origName}' (.{$ext}) tidak diizinkan. Format yang diterima: {$allowedList}.",
                ], 422);
            }

            // Batas ukuran per file
            if ($fileSize > $maxSizeBytes) {
                return response()->json([
                    'success' => false,
                    'message' => "Ukuran file '{$origName}' melebihi batas maksimal {$assignment->max_file_size_mb} MB.",
                ], 422);
            }
        }

        // Tentukan jenis upload
        $isSingleFile = count($uploadedFiles) === 1 && empty($relativePaths[0] ?? null);
        $uploadType = $isSingleFile ? 'single_file' : 'batch_folder';

        // Buat kode resi unik
        $receiptCode = 'KMP-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        $isLate = $assignment->deadline ? now()->greaterThan($assignment->deadline) : false;

        // Siapkan folder temporer lokal
        $batchId = uniqid('batch_');
        $tempDir = storage_path("app/temp_uploads/{$batchId}");
        File::makeDirectory($tempDir, 0755, true);

        try {
            // Ambil akun aktif Google Drive
            $activeAccount = $driveService->getActiveAccount();

            // Dapatkan folder tujuan di Google Drive
            $assignmentFolderId = $driveService->getAssignmentFolderId(
                $classroom,
                $assignment->subject,
                $assignment
            );

            $studentCleanName = $driveService->sanitizeName($validated['student_name']);
            $classCleanName = $driveService->sanitizeName($classroom->name);

            $targetFolderId = $assignmentFolderId;
            $gdriveTargetId = null;
            $gdriveWebViewLink = null;

            // Jika multi-file atau folder utuh, buat subfolder [KELAS]_[NAMA-SISWA]
            if (!$isSingleFile) {
                $studentFolderTitle = "{$classCleanName}_{$studentCleanName}";
                $targetFolderId = $driveService->createStudentFolder($studentFolderTitle, $assignmentFolderId);
                $gdriveTargetId = $targetFolderId;
            }

            $submission = Submission::create([
                'receipt_code' => $receiptCode,
                'drive_account_id' => $activeAccount?->id,
                'assignment_id' => $assignment->id,
                'classroom_id' => $classroom->id,
                'student_name' => $validated['student_name'],
                'upload_type' => $uploadType,
                'file_count' => count($uploadedFiles),
                'total_size_bytes' => $totalSizeBytes,
                'gdrive_target_id' => $gdriveTargetId,
                'gdrive_web_view_link' => null,
                'is_late' => $isLate,
                'ip_address' => $request->ip(),
                'submitted_at' => now(),
            ]);

            $savedItems = [];

            foreach ($uploadedFiles as $index => $file) {
                $originalName = $file->getClientOriginalName();
                $relativePath = $relativePaths[$index] ?? null;
                $ext = $file->getClientOriginalExtension();
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);

                // Pola penamaan file:
                // Single file: [KELAS]_[NAMA-SISWA]_[NAMA-FILE-ASLI].[EXT]
                // Folder/Multi file: tetap simpan nama asli (karena sudah di dalam subfolder siswa)
                if ($isSingleFile) {
                    $renamedFilename = "{$classCleanName}_{$studentCleanName}_{$driveService->sanitizeName($baseName)}.{$ext}";
                } else {
                    $renamedFilename = $originalName;
                }

                // Simpan sementara di storage local
                $tempFilePath = "{$tempDir}/{$index}_{$originalName}";
                copy($file->getRealPath(), $tempFilePath);

                // Unggah ke Google Drive
                $uploadResult = $driveService->uploadFile(
                    $tempFilePath,
                    $renamedFilename,
                    $targetFolderId,
                    $file->getClientMimeType()
                );

                // Segera hapus file temporer lokal setelah diunggah
                if (file_exists($tempFilePath)) {
                    @unlink($tempFilePath);
                }

                $item = SubmissionItem::create([
                    'submission_id' => $submission->id,
                    'original_filename' => $originalName,
                    'renamed_filename' => $renamedFilename,
                    'relative_path' => $relativePath,
                    'file_size_bytes' => $file->getSize(),
                    'mime_type' => $file->getClientMimeType(),
                    'gdrive_file_id' => $uploadResult['file_id'] ?? null,
                    'gdrive_web_view_link' => $uploadResult['web_view_link'] ?? null,
                ]);

                $savedItems[] = [
                    'original_name' => $originalName,
                    'renamed_name' => $renamedFilename,
                    'size_formatted' => $this->formatBytes($file->getSize()),
                ];

                if (!$gdriveWebViewLink && !empty($uploadResult['web_view_link'])) {
                    $gdriveWebViewLink = $uploadResult['web_view_link'];
                }
                if ($isSingleFile && !empty($uploadResult['file_id'])) {
                    $gdriveTargetId = $uploadResult['file_id'];
                }
            }

            $submission->update([
                'gdrive_target_id' => $gdriveTargetId,
                'gdrive_web_view_link' => $gdriveWebViewLink,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tugas Anda berhasil dikumpulkan!',
                'receipt' => [
                    'receipt_code' => $submission->receipt_code,
                    'student_name' => $submission->student_name,
                    'classroom' => $classroom->name,
                    'assignment_title' => $assignment->title,
                    'subject' => $assignment->subject->name,
                    'upload_type' => $isSingleFile ? '1 File Tunggal' : 'Multi-File / Folder Proyek',
                    'file_count' => $submission->file_count,
                    'total_size_formatted' => $this->formatBytes($totalSizeBytes),
                    'is_late' => $submission->is_late,
                    'submitted_at' => $submission->submitted_at->isoFormat('dddd, D MMMM Y - HH:mm'),
                    'files' => $savedItems,
                    'drive_link' => $submission->gdrive_web_view_link,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error("Gagal memproses pengumpulan tugas: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kendala saat mengunggah tugas: ' . $e->getMessage(),
            ], 500);
        } finally {
            // Pembersihan Otomatis File Sementara di storage/app/temp_uploads/{batch_id}
            unset($uploadedFiles);
            gc_collect_cycles();
            clearstatcache();
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
                if (File::isDirectory($tempDir)) {
                    @rmdir($tempDir);
                }
            }
        }
    }

    /**
     * Cek Resi Pengumpulan
     */
    public function checkReceipt(string $receiptCode): JsonResponse
    {
        $submission = Submission::with(['classroom', 'assignment.subject', 'items'])
            ->where('receipt_code', $receiptCode)
            ->first();

        if (!$submission) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found' => true,
            'receipt' => [
                'receipt_code' => $submission->receipt_code,
                'student_name' => $submission->student_name,
                'classroom' => $submission->classroom->name,
                'assignment_title' => $submission->assignment->title,
                'subject' => $submission->assignment->subject->name,
                'upload_type' => $submission->upload_type === 'single_file' ? '1 File Tunggal' : 'Folder / Multi File',
                'file_count' => $submission->file_count,
                'total_size_formatted' => $this->formatBytes($submission->total_size_bytes),
                'is_late' => $submission->is_late,
                'submitted_at' => $submission->submitted_at->isoFormat('dddd, D MMMM Y - HH:mm'),
                'drive_link' => $submission->gdrive_web_view_link,
            ],
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return "{$bytes} bytes";
    }
}
