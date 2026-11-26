<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\DriveAccount;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin Guru SMKN 1 Labuhan Maringgai
        User::firstOrCreate(
            ['email' => 'admin@smkn1labuhanmaringgai.sch.id'],
            [
                'name' => 'Guru DKV (Admin)',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Kelas
        $c1 = Classroom::firstOrCreate(['name' => 'X DKV 1'], ['level' => 'X', 'is_active' => true]);
        $c2 = Classroom::firstOrCreate(['name' => 'X DKV 2'], ['level' => 'X', 'is_active' => true]);
        $c3 = Classroom::firstOrCreate(['name' => 'XI DKV 1'], ['level' => 'XI', 'is_active' => true]);
        $c4 = Classroom::firstOrCreate(['name' => 'XI DKV 2'], ['level' => 'XI', 'is_active' => true]);
        $c5 = Classroom::firstOrCreate(['name' => 'XII DKV'], ['level' => 'XII', 'is_active' => true]);

        // Mata Pelajaran
        $s1 = Subject::firstOrCreate(['name' => 'Perangkat Lunak Desain'], ['code' => 'DKV-PLD', 'is_active' => true]);
        $s2 = Subject::firstOrCreate(['name' => 'Fotografi & Videografi'], ['code' => 'DKV-FOTO', 'is_active' => true]);
        $s3 = Subject::firstOrCreate(['name' => 'Desain Publikasi'], ['code' => 'DKV-PUB', 'is_active' => true]);
        $s4 = Subject::firstOrCreate(['name' => 'Nirmana 2D & 3D'], ['code' => 'DKV-NIR', 'is_active' => true]);
        $s5 = Subject::firstOrCreate(['name' => 'Dasar-Dasar Kreatif DKV'], ['code' => 'DKV-DS', 'is_active' => true]);

        // Akun Google Drive Standby Dummy (agar siap dikoneksikan via OAuth oleh admin)
        DriveAccount::firstOrCreate(
            ['account_name' => 'Google Drive Utama Guru'],
            [
                'google_email' => null,
                'priority' => 1,
                'is_active' => true,
                'is_full' => false,
                'storage_used_bytes' => 0,
                'storage_limit_bytes' => 15 * 1024 * 1024 * 1024, // 15 GB default
            ]
        );

        // Sample Tugas
        Assignment::firstOrCreate(
            ['slug' => 'tugas-01-desain-kemasan-kopi-robusta'],
            [
                'subject_id' => $s1->id,
                'classroom_id' => $c3->id, // XI DKV 1
                'title' => 'Tugas 01 - Desain Kemasan Kopi Robusta',
                'description' => "Buatlah mockup desain kemasan sachet dan pouch untuk produk Kopi Robusta Lampung Timur. File kemasan harus menyertakan logo, label informasi gizi, dan komposisi.\n\nUnggah file dalam format PDF/CDR/PSD atau gambar resolusi tinggi (JPG/PNG).",
                'allowed_extensions' => ['pdf', 'cdr', 'psd', 'ai', 'jpg', 'png', 'zip'],
                'max_file_size_mb' => 100,
                'deadline' => now()->addDays(7)->setTime(23, 59, 0),
                'is_active' => true,
            ]
        );

        Assignment::firstOrCreate(
            ['slug' => 'tugas-02-fotografi-komersial-produk-umkm'],
            [
                'subject_id' => $s2->id,
                'classroom_id' => $c4->id, // XI DKV 2
                'title' => 'Tugas 02 - Fotografi Komersial Produk UMKM',
                'description' => "Kumpulkan hasil foto produk UMKM lokal (minimal 3 sudut pengambilan: Front, Top-down 45 derajat, dan Detail). Anda dapat mengunggah 1 folder foto utuh atau file ZIP/kumpulan JPG.",
                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'zip', 'rar'],
                'max_file_size_mb' => 200,
                'deadline' => now()->addDays(4)->setTime(23, 59, 0),
                'is_active' => true,
            ]
        );

        Assignment::firstOrCreate(
            ['slug' => 'portofolio-desain-grafis-akhir-semester'],
            [
                'subject_id' => $s3->id,
                'classroom_id' => null, // Terbuka untuk semua kelas
                'title' => 'Portofolio Desain Grafis Akhir Semester',
                'description' => "Kumpulkan seluruh bundel karya terbaik semester ini dalam 1 folder utuh atau arsip ZIP berisi katalog karya dan file sumber (source files).",
                'allowed_extensions' => ['pdf', 'zip', 'rar', 'psd', 'ai', 'cdr'],
                'max_file_size_mb' => 350,
                'deadline' => now()->addDays(14)->setTime(23, 59, 0),
                'is_active' => true,
            ]
        );
    }
}
