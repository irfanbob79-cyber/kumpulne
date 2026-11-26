<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\DriveAccount;
use App\Models\Subject;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class KumpulneTest extends TestCase
{
    use RefreshDatabase;

    protected Classroom $classroom;
    protected Subject $subject;
    protected Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(storage_path('app/temp_uploads'));

        $this->classroom = Classroom::firstOrCreate(['name' => 'XI DKV 1'], ['level' => 'XI', 'is_active' => true]);
        $this->subject = Subject::firstOrCreate(['name' => 'Perangkat Lunak Desain'], ['code' => 'DKV-PLD', 'is_active' => true]);
        $this->assignment = Assignment::firstOrCreate(
            ['slug' => 'tugas-uji-coba-desain'],
            [
                'subject_id' => $this->subject->id,
                'classroom_id' => $this->classroom->id,
                'title' => 'Tugas Uji Coba Desain Kemasan',
                'description' => 'Instruksi pengerjaan uji coba.',
                'allowed_extensions' => ['pdf', 'png', 'jpg', 'zip'],
                'max_file_size_mb' => 50,
                'deadline' => now()->addDays(5),
                'is_active' => true,
            ]
        );
    }

    public function test_home_portal_displays_active_assignments(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Kumpulne');
        $response->assertSee('SMKN 1 Labuhan Maringgai');
        $response->assertSee('Tugas Uji Coba Desain Kemasan');
    }

    public function test_direct_assignment_page_loads(): void
    {
        $response = $this->get("/kumpul/{$this->assignment->slug}");
        $response->assertStatus(200);
        $response->assertSee('Tugas Uji Coba Desain Kemasan');
        $response->assertSee('Area Unggah File');
    }

    public function test_student_can_upload_single_file_and_get_receipt(): void
    {
        $fakeFile = UploadedFile::fake()->create('kemasan_kopi.pdf', 500, 'application/pdf');

        $response = $this->postJson("/kumpul/{$this->assignment->slug}", [
            'student_name' => 'Budi Santoso',
            'classroom_id' => $this->classroom->id,
            'files' => [$fakeFile],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'receipt' => [
                'receipt_code',
                'student_name',
                'classroom',
                'assignment_title',
                'total_size_formatted',
                'files',
            ],
        ]);

        $this->assertDatabaseHas('submissions', [
            'student_name' => 'Budi Santoso',
            'assignment_id' => $this->assignment->id,
            'classroom_id' => $this->classroom->id,
            'upload_type' => 'single_file',
        ]);
    }

    public function test_student_can_upload_batch_files(): void
    {
        $file1 = UploadedFile::fake()->create('desain_depan.png', 300, 'image/png');
        $file2 = UploadedFile::fake()->create('desain_belakang.png', 400, 'image/png');

        $response = $this->postJson("/kumpul/{$this->assignment->slug}", [
            'student_name' => 'Siti Aminah',
            'classroom_id' => $this->classroom->id,
            'files' => [$file1, $file2],
            'relative_paths' => ['mockup/depan.png', 'mockup/belakang.png'],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('submissions', [
            'student_name' => 'Siti Aminah',
            'upload_type' => 'batch_folder',
            'file_count' => 2,
        ]);
    }

    public function test_upload_rejects_dangerous_files(): void
    {
        $dangerousFile = UploadedFile::fake()->create('malicious_script.php', 10, 'text/x-php');

        $response = $this->postJson("/kumpul/{$this->assignment->slug}", [
            'student_name' => 'Hacker Cilik',
            'classroom_id' => $this->classroom->id,
            'files' => [$dangerousFile],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'success' => false,
        ]);
    }

    public function test_upload_rejects_unallowed_extension(): void
    {
        $unallowedFile = UploadedFile::fake()->create('document.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->postJson("/kumpul/{$this->assignment->slug}", [
            'student_name' => 'Dodi',
            'classroom_id' => $this->classroom->id,
            'files' => [$unallowedFile],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'success' => false,
        ]);
    }

    public function test_temporary_upload_folder_is_cleaned_up_after_upload(): void
    {
        $fakeFile = UploadedFile::fake()->create('test_clean.pdf', 200, 'application/pdf');

        $this->postJson("/kumpul/{$this->assignment->slug}", [
            'student_name' => 'Umar',
            'classroom_id' => $this->classroom->id,
            'files' => [$fakeFile],
        ]);

        $tempPath = storage_path('app/temp_uploads');
        if (File::isDirectory($tempPath)) {
            $directories = File::directories($tempPath);
            $this->assertEmpty($directories, 'Folder temp_uploads harus kosong setelah proses pengunggahan.');
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_student_can_check_receipt_code(): void
    {
        $fakeFile = UploadedFile::fake()->create('mockup.pdf', 100, 'application/pdf');

        $uploadResponse = $this->postJson("/kumpul/{$this->assignment->slug}", [
            'student_name' => 'Fauzi',
            'classroom_id' => $this->classroom->id,
            'files' => [$fakeFile],
        ]);

        $receiptCode = $uploadResponse->json('receipt.receipt_code');

        $receiptResponse = $this->getJson("/resi/{$receiptCode}");
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertJson([
            'found' => true,
            'receipt' => [
                'receipt_code' => $receiptCode,
                'student_name' => 'Fauzi',
            ],
        ]);
    }

    public function test_admin_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_admin_user_can_access_filament_panel_in_production(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->assertTrue($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')));

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);
    }
}
