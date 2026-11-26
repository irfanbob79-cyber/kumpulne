<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kumpulne - Portal Pengumpulan Tugas Siswa')</title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .dropzone-active {
            border-color: #16a34a !important;
            background-color: #f0fdf4 !important;
            transform: scale(1.01);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans" x-data="studentIdentity()" x-init="initIdentity()">

    <!-- Header Navbar -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand -->
            <a href="{{ route('portal.index') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-emerald-400 flex items-center justify-center text-white shadow-md shadow-brand-500/20 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-xl tracking-tight text-slate-900">Kumpulne</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider bg-brand-100 text-brand-800 px-2 py-0.5 rounded-full">Dropzone</span>
                    </div>
                    <p class="text-xs text-slate-500 hidden sm:block">SMKN 1 Labuhan Maringgai</p>
                </div>
            </a>

            <!-- Right Actions: Student Info & Admin Link -->
            <div class="flex items-center gap-3">
                <!-- Identitas Siswa (LocalStorage) -->
                <div x-cloak x-show="studentName" class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200/80 px-3 py-1.5 rounded-full border border-slate-200 transition-colors cursor-pointer" @click="openEditIdentityModal()">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span class="text-xs font-medium text-slate-700">
                        Halo, <strong x-text="studentName" class="font-semibold text-slate-900"></strong> 
                        <span class="text-slate-500" x-show="classroomName">(<span x-text="classroomName"></span>)</span>
                    </span>
                    <button type="button" class="text-[11px] font-semibold text-brand-700 hover:underline ml-1">
                        [Ganti]
                    </button>
                </div>

                <div x-cloak x-show="!studentName">
                    <button type="button" @click="openEditIdentityModal()" class="text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 px-3 py-1.5 rounded-full border border-slate-200 hover:bg-slate-200/80 transition-colors">
                        Set Identitas Siswa
                    </button>
                </div>

                <!-- Admin Link -->
                <a href="{{ url('/admin') }}" class="p-2 text-slate-500 hover:text-slate-800 rounded-lg hover:bg-slate-100 transition-colors" title="Panel Guru / Admin">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Global Identity Edit Modal -->
    <div x-cloak x-show="showIdentityModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4" @click.away="showIdentityModal = false">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-sm">
                        ID
                    </div>
                    <h3 class="font-bold text-slate-800 text-lg">Identitas Siswa</h3>
                </div>
                <button type="button" @click="showIdentityModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p class="text-xs text-slate-500">
                Identitas ini disimpan otomatis di browser HP/Laptop Anda sehingga Anda tidak perlu mengetik nama lagi setiap mengumpulkan tugas.
            </p>
            <form @submit.prevent="saveIdentity()" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap Siswa</label>
                    <input type="text" x-model="tempName" required placeholder="contoh: Ahmad Fauzi" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pilih Kelas</label>
                    <select x-model="tempClassId" @change="onClassChange($event)" required class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                        <option value="">-- Pilih Kelas Anda --</option>
                        @foreach(\App\Models\Classroom::where('is_active', true)->orderBy('name')->get() as $c)
                            <option value="{{ $c->id }}" data-name="{{ $c->name }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showIdentityModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm shadow-brand-500/30">Simpan Identitas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-12 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <p>© {{ date('Y') }} <strong>Kumpulne</strong> — Portal Pengumpulan Tugas Siswa SMKN 1 Labuhan Maringgai.</p>
            <p class="text-slate-400">Direct-to-Google Drive • Resumable Upload • Multi-Account</p>
        </div>
    </footer>

    <script>
        function studentIdentity() {
            return {
                studentName: '',
                classroomId: '',
                classroomName: '',
                showIdentityModal: false,
                tempName: '',
                tempClassId: '',
                tempClassName: '',

                initIdentity() {
                    const saved = localStorage.getItem('kumpulne_student');
                    if (saved) {
                        try {
                            const data = JSON.parse(saved);
                            this.studentName = data.name || '';
                            this.classroomId = data.classroom_id || '';
                            this.classroomName = data.classroom_name || '';
                        } catch(e){}
                    }
                },

                openEditIdentityModal() {
                    this.tempName = this.studentName;
                    this.tempClassId = this.classroomId;
                    this.tempClassName = this.classroomName;
                    this.showIdentityModal = true;
                },

                onClassChange(e) {
                    const selected = e.target.options[e.target.selectedIndex];
                    this.tempClassName = selected ? selected.getAttribute('data-name') : '';
                },

                saveIdentity() {
                    this.studentName = this.tempName.trim();
                    this.classroomId = this.tempClassId;
                    this.classroomName = this.tempClassName;

                    localStorage.setItem('kumpulne_student', JSON.stringify({
                        name: this.studentName,
                        classroom_id: this.classroomId,
                        classroom_name: this.classroomName
                    }));

                    this.showIdentityModal = false;
                }
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
