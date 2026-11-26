@extends('layouts.portal')

@section('title', $assignment->title . ' - Kumpulne')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-6" x-data="assignmentUploader()" x-init="initUploader()">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500">
        <a href="{{ route('portal.index') }}" class="hover:text-brand-600 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Beranda
        </a>
        <span>/</span>
        <span class="text-slate-400">{{ $assignment->subject->name }}</span>
        <span>/</span>
        <span class="text-slate-800 truncate max-w-xs">{{ $assignment->title }}</span>
    </nav>

    <!-- Header Card: Assignment Info -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        {{ $assignment->subject->name }}
                    </span>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700">
                        {{ $assignment->classroom ? $assignment->classroom->name : 'Semua Kelas' }}
                    </span>
                    @if(!$assignment->is_active)
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-100 text-rose-800">
                            Pengumpulan Ditutup
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $assignment->title }}
                </h1>
            </div>

            <!-- Countdown / Deadline Badge -->
            @if($assignment->deadline)
                <div class="bg-slate-50 border border-slate-200/80 p-3.5 rounded-2xl sm:text-right shrink-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Batas Waktu (Deadline)</span>
                    <div class="text-sm font-extrabold text-slate-800">
                        {{ $assignment->deadline->isoFormat('dddd, D MMMM Y') }}
                    </div>
                    <div class="text-xs font-bold mt-0.5 {{ $assignment->isExpired() ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ $assignment->deadline->format('H:i') }} WIB • 
                        @if($assignment->isExpired())
                            <span class="text-rose-600">Sudah Berakhir</span>
                        @else
                            <span>{{ $assignment->deadline->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Description / Instructions -->
        @if($assignment->description)
            <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 sm:p-5 text-sm text-slate-700 space-y-1.5 leading-relaxed">
                <strong class="text-xs font-bold uppercase tracking-wider text-amber-900 block flex items-center gap-1.5">
                    <span>📌</span> Instruksi Pengerjaan:
                </strong>
                <p class="whitespace-pre-line">{{ $assignment->description }}</p>
            </div>
        @endif

        <!-- Constraints bar -->
        <div class="flex flex-wrap items-center gap-4 pt-2 text-xs text-slate-600 border-t border-slate-100">
            <div class="flex items-center gap-1.5">
                <span class="text-slate-400">Format Diizinkan:</span>
                <span class="font-mono font-bold text-slate-800 uppercase">
                    {{ $assignment->allowed_extensions ? implode(', ', $assignment->allowed_extensions) : 'Semua Format File' }}
                </span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-slate-400">Maksimal Ukuran File:</span>
                <span class="font-bold text-slate-800">{{ $assignment->max_file_size_mb }} MB</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-slate-400">Penyimpanan:</span>
                <span class="font-bold text-slate-800">Direct to Google Drive</span>
            </div>
        </div>
    </div>

    @if(!$assignment->is_active)
        <!-- Closed Notice -->
        <div class="bg-rose-50 border border-rose-200 rounded-3xl p-8 text-center space-y-3">
            <div class="text-4xl">🔒</div>
            <h3 class="text-lg font-bold text-rose-900">Pengumpulan Tugas Telah Ditutup</h3>
            <p class="text-xs text-rose-700 max-w-md mx-auto">
                Guru telah menutup akses pengumpulan untuk tugas ini. Hubungi guru mata pelajaran yang bersangkutan jika Anda memerlukan perpanjangan waktu.
            </p>
            <a href="{{ route('portal.index') }}" class="inline-block mt-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-colors">
                Kembali ke Beranda
            </a>
        </div>
    @else
        <!-- UPLOAD DROPZONE AREA -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-6">
            
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Area Unggah File & Folder</h2>
                    <p class="text-xs text-slate-500">Seret file atau klik tombol di bawah untuk memilih file tugasmu.</p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Tombol Upload File -->
                    <label class="cursor-pointer px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Pilih File</span>
                        <input type="file" multiple @change="handleFileSelect($event)" class="hidden">
                    </label>

                    <!-- Tombol Upload 1 Folder Utuh -->
                    <label class="cursor-pointer px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-semibold flex items-center gap-1.5 transition-colors" title="Unggah seluruh folder proyek sekaligus">
                        <span>📁</span>
                        <span>Pilih 1 Folder Utuh</span>
                        <input type="file" webkitdirectory directory multiple @change="handleFolderSelect($event)" class="hidden">
                    </label>
                </div>
            </div>

            <!-- Big Drag & Drop Box -->
            <div 
                class="border-2 border-dashed border-slate-300 hover:border-brand-500 rounded-3xl p-8 sm:p-12 text-center transition-all cursor-pointer relative bg-slate-50/50"
                :class="{ 'dropzone-active': isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop($event)"
                @click="$refs.fileInput.click()"
            >
                <input type="file" x-ref="fileInput" multiple @change="handleFileSelect($event)" class="hidden">

                <div class="space-y-3 pointer-events-none">
                    <div class="w-16 h-16 mx-auto rounded-3xl bg-brand-100/70 border border-brand-200 flex items-center justify-center text-3xl">
                        ☁️
                    </div>
                    <div class="space-y-1">
                        <p class="font-extrabold text-slate-800 text-base">
                            Tarik & Lepas File atau Folder ke Sini
                        </p>
                        <p class="text-xs text-slate-500">
                            Mendukung <strong class="text-slate-700">1 file tunggal</strong>, <strong class="text-slate-700">banyak file</strong>, atau <strong class="text-slate-700">1 folder proyek utuh</strong>.
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-white rounded-full border border-slate-200 text-[11px] font-medium text-slate-600 shadow-sm">
                        <span>Maks. {{ $assignment->max_file_size_mb }} MB/file</span>
                        <span>•</span>
                        <span>Format: {{ $assignment->allowed_extensions ? implode(', ', array_slice($assignment->allowed_extensions, 0, 5)) : 'Semua format' }}</span>
                    </div>
                </div>
            </div>

            <!-- Staged Files Preview List -->
            <div x-cloak x-show="stagedFiles.length > 0" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                        <span>📋</span> File Siap Dikirim (<span x-text="stagedFiles.length"></span> item • <span x-text="formatBytes(totalStagedSize)"></span>)
                    </h3>
                    <button type="button" @click="clearStagedFiles()" class="text-xs text-rose-600 hover:underline font-semibold">
                        Hapus Semua
                    </button>
                </div>

                <div class="max-h-60 overflow-y-auto space-y-2 pr-1 border border-slate-200 rounded-2xl p-3 bg-slate-50">
                    <template x-for="(file, index) in stagedFiles" :key="index">
                        <div class="flex items-center justify-between p-2.5 bg-white rounded-xl border border-slate-200/80 text-xs">
                            <div class="flex items-center gap-2.5 truncate">
                                <span class="text-base shrink-0">📄</span>
                                <div class="truncate">
                                    <p class="font-bold text-slate-800 truncate" x-text="file.name"></p>
                                    <p class="text-[11px] text-slate-400 font-mono" x-show="file.relativePath" x-text="file.relativePath"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0 ml-3">
                                <span class="font-mono text-slate-500" x-text="formatBytes(file.size)"></span>
                                <button type="button" @click="removeStagedFile(index)" class="text-slate-400 hover:text-rose-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Submit Button -->
                <div class="pt-3">
                    <button 
                        type="button" 
                        @click="startSubmissionFlow()" 
                        :disabled="isUploading"
                        class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-brand-600 to-emerald-600 hover:from-brand-700 hover:to-emerald-700 text-white font-extrabold text-sm shadow-xl shadow-brand-500/25 flex items-center justify-center gap-2 transition-all transform active:scale-[0.99] disabled:opacity-50"
                    >
                        <span>🚀</span>
                        <span x-text="isUploading ? 'Sedang Mengunggah...' : 'Kumpulkan Tugas Sekarang'"></span>
                    </button>
                </div>
            </div>

            <!-- Upload Progress Bar (Live) -->
            <div x-cloak x-show="isUploading" class="space-y-2 bg-emerald-50/80 border border-emerald-200 p-4 rounded-2xl">
                <div class="flex items-center justify-between text-xs font-bold text-emerald-900">
                    <span class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Mengunggah file ke Google Drive...</span>
                    </span>
                    <span class="font-mono text-sm" x-text="uploadProgress + '%'"></span>
                </div>
                <div class="w-full bg-emerald-200/60 h-2.5 rounded-full overflow-hidden">
                    <div class="bg-brand-600 h-full rounded-full transition-all duration-150" :style="'width: ' + uploadProgress + '%'"></div>
                </div>
                <p class="text-[11px] text-emerald-700">Mohon jangan menutup halaman browser sampai proses pengumpulan selesai.</p>
            </div>

        </div>
    @endif

    <!-- MODAL 1: KONFIRMASI / PENGISIAN IDENTITAS SISWA KILAT -->
    <div x-cloak x-show="showConfirmIdentityModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-5" @click.away="showConfirmIdentityModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-base">
                        👤
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-lg">Konfirmasi Identitas</h3>
                        <p class="text-xs text-slate-500">Pastikan nama dan kelas sudah sesuai.</p>
                    </div>
                </div>
                <button type="button" @click="showConfirmIdentityModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Form Identitas -->
            <form @submit.prevent="executeUpload()" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap Siswa</label>
                    <input type="text" x-model="studentName" required placeholder="contoh: Siti Aminah" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 font-medium">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Kelas</label>
                    @if($assignment->classroom_id)
                        <!-- Terkunci untuk kelas khusus ini -->
                        <input type="text" disabled value="{{ $assignment->classroom->name }}" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-100 text-slate-700 font-bold">
                        <input type="hidden" x-model="classroomId" value="{{ $assignment->classroom_id }}">
                        <p class="text-[11px] text-slate-400 mt-1">Tugas ini khusus untuk {{ $assignment->classroom->name }}.</p>
                    @else
                        <!-- Pilihan semua kelas -->
                        <select x-model="classroomId" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 font-medium">
                            <option value="">-- Pilih Kelas Anda --</option>
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80 text-xs text-slate-600">
                    💡 <strong>Tips:</strong> Nama & kelas ini akan otomatis disimpan di perangkat Anda agar tidak perlu mengetik ulang di tugas berikutnya.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showConfirmIdentityModal = false" class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2.5 text-xs font-extrabold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-md shadow-brand-500/30 flex items-center gap-1.5">
                        <span>Ya, Kirim Sekarang</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: KARTU TANDA TERIMA DIGITAL (RESI BUKTI PENGUMPULAN) -->
    <div x-cloak x-show="showReceiptModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-6" id="printableReceipt">
            
            <!-- Receipt Header -->
            <div class="text-center space-y-2 pb-4 border-b border-dashed border-slate-200">
                <div class="w-14 h-14 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl shadow-inner">
                    ✓
                </div>
                <h3 class="text-xl font-extrabold text-slate-900">Bukti Tanda Terima Tugas</h3>
                <p class="text-xs text-slate-500">SMKN 1 Labuhan Maringgai • Kumpulne Portal</p>
                
                <!-- Receipt Code Box -->
                <div class="mt-3 inline-block bg-slate-100 border border-slate-200 px-4 py-2 rounded-2xl">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-widest">Nomor Resi Unik</span>
                    <span class="font-mono font-extrabold text-lg text-brand-700 tracking-wider" x-text="receiptData?.receipt_code"></span>
                </div>
            </div>

            <!-- Receipt Details Grid -->
            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Nama Siswa</span>
                    <strong class="font-bold text-slate-800 text-sm" x-text="receiptData?.student_name"></strong>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Kelas</span>
                    <strong class="font-semibold text-slate-800" x-text="receiptData?.classroom"></strong>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Tugas / Mata Pelajaran</span>
                    <strong class="font-semibold text-slate-800 text-right truncate max-w-xs" x-text="receiptData?.assignment_title"></strong>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Waktu Pengumpulan</span>
                    <strong class="font-semibold text-slate-800" x-text="receiptData?.submitted_at"></strong>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Status Ketepatan</span>
                    <span class="px-2 py-0.5 rounded-full font-bold text-[11px]" :class="receiptData?.is_late ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800'" x-text="receiptData?.is_late ? 'Terlambat' : 'Tepat Waktu'"></span>
                </div>
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Jumlah File & Ukuran</span>
                    <strong class="font-semibold text-slate-800" x-text="receiptData?.file_count + ' file (' + receiptData?.total_size_formatted + ')'"></strong>
                </div>

                <!-- Files Breakdown -->
                <div class="pt-2">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Rincian File Terkirim:</span>
                    <div class="bg-slate-50 rounded-xl p-2.5 border border-slate-200/80 max-h-32 overflow-y-auto space-y-1 font-mono text-[11px]">
                        <template x-for="f in (receiptData?.files || [])">
                            <div class="flex items-center justify-between text-slate-700">
                                <span class="truncate pr-2" x-text="f.original_name"></span>
                                <span class="text-slate-400 shrink-0" x-text="f.size_formatted"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Receipt Actions -->
            <div class="flex flex-col sm:flex-row items-center gap-2 pt-2">
                <button type="button" @click="copyReceiptCode()" class="w-full sm:w-1/2 py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <span x-text="copiedResi ? 'Resi Tersalin!' : 'Salin Kode Resi'"></span>
                </button>
                <button type="button" @click="window.print()" class="w-full sm:w-1/2 py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak Bukti Resi</span>
                </button>
            </div>

            <div class="text-center pt-1">
                <button type="button" @click="resetAfterSuccess()" class="text-xs text-brand-700 hover:underline font-bold">
                    Kumpulkan File Lain untuk Tugas Ini
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function assignmentUploader() {
        return {
            isDragging: false,
            stagedFiles: [],
            isUploading: false,
            uploadProgress: 0,
            showConfirmIdentityModal: false,
            showReceiptModal: false,
            receiptData: null,
            copiedResi: false,

            studentName: '',
            classroomId: '{{ $assignment->classroom_id ?? "" }}',

            initUploader() {
                const saved = localStorage.getItem('kumpulne_student');
                if (saved) {
                    try {
                        const parsed = JSON.parse(saved);
                        this.studentName = parsed.name || '';
                        @if(!$assignment->classroom_id)
                            this.classroomId = parsed.classroom_id || '';
                        @endif
                    } catch(e){}
                }
            },

            get totalStagedSize() {
                return this.stagedFiles.reduce((acc, f) => acc + f.size, 0);
            },

            handleFileSelect(e) {
                const files = Array.from(e.target.files);
                this.addFiles(files);
                e.target.value = '';
            },

            handleFolderSelect(e) {
                const files = Array.from(e.target.files);
                files.forEach(f => {
                    this.stagedFiles.push({
                        fileObject: f,
                        name: f.name,
                        size: f.size,
                        relativePath: f.webkitRelativePath || ''
                    });
                });
                e.target.value = '';
            },

            async handleDrop(e) {
                this.isDragging = false;
                const items = e.dataTransfer.items;
                if (!items) return;

                const entries = [];
                for (let i = 0; i < items.length; i++) {
                    const item = items[i];
                    if (item.kind === 'file') {
                        const entry = item.webkitGetAsEntry ? item.webkitGetAsEntry() : null;
                        if (entry) {
                            entries.push(entry);
                        } else {
                            const f = item.getAsFile();
                            if (f) this.addFiles([f]);
                        }
                    }
                }

                if (entries.length > 0) {
                    for (const entry of entries) {
                        await this.scanEntry(entry, '');
                    }
                }
            },

            async scanEntry(entry, path) {
                if (entry.isFile) {
                    return new Promise((resolve) => {
                        entry.file((f) => {
                            this.stagedFiles.push({
                                fileObject: f,
                                name: f.name,
                                size: f.size,
                                relativePath: path ? `${path}/${f.name}` : f.name
                            });
                            resolve();
                        });
                    });
                } else if (entry.isDirectory) {
                    const dirReader = entry.createReader();
                    return new Promise((resolve) => {
                        dirReader.readEntries(async (entries) => {
                            for (const child of entries) {
                                await this.scanEntry(child, path ? `${path}/${entry.name}` : entry.name);
                            }
                            resolve();
                        });
                    });
                }
            },

            addFiles(files) {
                files.forEach(f => {
                    this.stagedFiles.push({
                        fileObject: f,
                        name: f.name,
                        size: f.size,
                        relativePath: ''
                    });
                });
            },

            removeStagedFile(index) {
                this.stagedFiles.splice(index, 1);
            },

            clearStagedFiles() {
                this.stagedFiles = [];
            },

            startSubmissionFlow() {
                if (this.stagedFiles.length === 0) {
                    alert('Silakan pilih file tugas terlebih dahulu.');
                    return;
                }
                this.showConfirmIdentityModal = true;
            },

            executeUpload() {
                if (!this.studentName.trim()) {
                    alert('Nama lengkap siswa wajib diisi.');
                    return;
                }
                if (!this.classroomId) {
                    alert('Kelas wajib dipilih.');
                    return;
                }

                // Simpan ke localStorage
                localStorage.setItem('kumpulne_student', JSON.stringify({
                    name: this.studentName.trim(),
                    classroom_id: this.classroomId
                }));

                this.showConfirmIdentityModal = false;
                this.isUploading = true;
                this.uploadProgress = 0;

                const formData = new FormData();
                formData.append('student_name', this.studentName.trim());
                formData.append('classroom_id', this.classroomId);

                this.stagedFiles.forEach((item, index) => {
                    formData.append('files[]', item.fileObject);
                    if (item.relativePath) {
                        formData.append(`relative_paths[${index}]`, item.relativePath);
                    }
                });

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '{{ route("portal.submit", $assignment->slug) }}', true);
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        this.uploadProgress = Math.round((e.loaded / e.total) * 95); // 95% upload bytes
                    }
                };

                xhr.onload = () => {
                    this.uploadProgress = 100;
                    this.isUploading = false;

                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (xhr.status === 200 && res.success) {
                            this.receiptData = res.receipt;
                            this.stagedFiles = [];
                            this.showReceiptModal = true;
                        } else {
                            alert(res.message || 'Gagal mengunggah file. Silakan periksa kembali file Anda.');
                        }
                    } catch (err) {
                        alert('Terjadi kesalahan pada server saat memproses unggahan.');
                    }
                };

                xhr.onerror = () => {
                    this.isUploading = false;
                    alert('Koneksi jaringan terputus atau terjadi kesalahan saat mengunggah.');
                };

                xhr.send(formData);
            },

            copyReceiptCode() {
                if (!this.receiptData?.receipt_code) return;
                navigator.clipboard.writeText(this.receiptData.receipt_code);
                this.copiedResi = true;
                setTimeout(() => this.copiedResi = false, 2500);
            },

            resetAfterSuccess() {
                this.showReceiptModal = false;
                this.receiptData = null;
                this.uploadProgress = 0;
            },

            formatBytes(bytes) {
                if (bytes >= 1024 * 1024 * 1024) return (bytes / 1024 / 1024 / 1024).toFixed(2) + ' GB';
                if (bytes >= 1024 * 1024) return (bytes / 1024 / 1024).toFixed(2) + ' MB';
                if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return bytes + ' B';
            }
        }
    }
</script>
@endpush
