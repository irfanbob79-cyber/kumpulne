@extends('layouts.portal')

@section('title', 'Kumpulne - Grid Folder Tugas Siswa')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="folderExplorer()">

    <!-- Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-700 via-brand-700 to-teal-800 text-white p-8 sm:p-12 shadow-xl shadow-brand-900/10">
        <div class="relative z-10 max-w-2xl space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-semibold text-emerald-100">
                <span>📁</span> Portal Praktik & Desain DKV
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                Kumpulkan Tugas & Proyek Tanpa Perlu Login
            </h1>
            <p class="text-sm sm:text-base text-emerald-100/90 leading-relaxed">
                Pilih folder tugas di bawah ini atau buka link yang dibagikan gurumu. Kamu bisa mengunggah 1 file, banyak file, maupun 1 folder utuh langsung ke Google Drive guru.
            </p>

            <!-- Search Bar -->
            <div class="pt-2">
                <div class="relative max-w-lg">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" x-model="searchQuery" placeholder="Cari judul tugas, mata pelajaran, atau kelas..." class="w-full pl-10 pr-4 py-3 bg-white text-slate-900 placeholder-slate-400 rounded-2xl shadow-lg border-0 text-sm focus:outline-none focus:ring-4 focus:ring-brand-400/50">
                </div>
            </div>
        </div>

        <!-- Decorative background graphics -->
        <div class="absolute -right-12 -bottom-16 w-80 h-80 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
        <div class="absolute right-12 top-6 opacity-10 hidden md:block">
            <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M19.414 6l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-2.586z"></path></svg>
        </div>
    </div>

    <!-- Filter Chips Section -->
    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-extrabold text-xl text-slate-900 flex items-center gap-2">
                <span>📂</span> Folder Tugas Aktif
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700" x-text="filteredAssignments.length"></span>
            </h2>

            <!-- Reset Filter Button -->
            <button x-show="selectedClass !== '' || selectedSubject !== '' || searchQuery !== ''" @click="resetFilters()" class="text-xs font-semibold text-brand-700 hover:text-brand-800 flex items-center gap-1">
                <span>✕</span> Reset Semua Filter
            </button>
        </div>

        <!-- Filter Kelas -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <span class="text-xs font-bold text-slate-500 whitespace-nowrap">Kelas:</span>
            <button type="button" @click="selectedClass = ''" :class="selectedClass === '' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap shadow-sm">
                Semua Kelas
            </button>
            @foreach($classrooms as $c)
                <button type="button" @click="selectedClass = '{{ $c->id }}'" :class="selectedClass === '{{ $c->id }}' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap shadow-sm">
                    {{ $c->name }}
                </button>
            @endforeach
        </div>

        <!-- Filter Mata Pelajaran -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <span class="text-xs font-bold text-slate-500 whitespace-nowrap">Mapel:</span>
            <button type="button" @click="selectedSubject = ''" :class="selectedSubject === '' ? 'bg-brand-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap shadow-sm">
                Semua Mapel
            </button>
            @foreach($subjects as $s)
                <button type="button" @click="selectedSubject = '{{ $s->id }}'" :class="selectedSubject === '{{ $s->id }}' ? 'bg-brand-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all whitespace-nowrap shadow-sm">
                    {{ $s->name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Assignments Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="item in filteredAssignments" :key="item.id">
            <div class="bg-white rounded-2xl border border-slate-200/80 hover:border-brand-500/50 shadow-sm hover:shadow-xl transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                <div class="p-6 space-y-4">
                    <!-- Top Info: Subject & Classroom -->
                    <div class="flex items-start justify-between gap-2">
                        <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200" x-text="item.subject_name"></span>
                        <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700" x-text="item.classroom_name"></span>
                    </div>

                    <!-- Title & Icon -->
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 shrink-0 rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                            📁
                        </div>
                        <div class="space-y-1">
                            <a :href="'/kumpul/' + item.slug" class="font-bold text-slate-900 group-hover:text-brand-600 text-base leading-snug line-clamp-2 transition-colors" x-text="item.title"></a>
                            <p class="text-xs text-slate-500 line-clamp-2" x-text="item.description || 'Tidak ada deskripsi khusus.'"></p>
                        </div>
                    </div>

                    <!-- Meta specs -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 space-y-1.5 text-xs text-slate-600">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Format Diizinkan:</span>
                            <span class="font-mono font-semibold uppercase text-slate-700" x-text="item.allowed_extensions ? item.allowed_extensions.join(', ') : 'Semua format'"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Maksimal Ukuran:</span>
                            <span class="font-semibold text-slate-700" x-text="item.max_file_size_mb + ' MB'"></span>
                        </div>
                    </div>
                </div>

                <!-- Card Footer: Deadline & Action -->
                <div class="px-6 py-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Batas Waktu:</span>
                        <span class="text-xs font-semibold" :class="item.is_expired ? 'text-rose-600 font-bold' : 'text-slate-700'" x-text="item.deadline_formatted || 'Tanpa Batas'"></span>
                    </div>
                    <a :href="'/kumpul/' + item.slug" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-500/20 group-hover:translate-x-0.5 transition-all">
                        <span>Buka Folder</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-cloak x-show="filteredAssignments.length === 0" class="text-center py-16 bg-white rounded-3xl border border-dashed border-slate-200 p-8 space-y-3">
        <div class="text-5xl">🔍</div>
        <h3 class="font-bold text-slate-800 text-lg">Tidak ada folder tugas yang cocok</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
            Coba ubah kata kunci pencarian Anda atau reset pilihan filter kelas dan mata pelajaran.
        </p>
        <button type="button" @click="resetFilters()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors">
            Reset Filter
        </button>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function folderExplorer() {
        return {
            searchQuery: '',
            selectedClass: '',
            selectedSubject: '',
            allAssignments: @json($assignmentsJson),

            get filteredAssignments() {
                return this.allAssignments.filter(item => {
                    // Match Search
                    const query = this.searchQuery.toLowerCase().trim();
                    const matchesSearch = !query 
                        || item.title.toLowerCase().includes(query)
                        || item.subject_name.toLowerCase().includes(query)
                        || item.classroom_name.toLowerCase().includes(query);

                    // Match Class
                    const matchesClass = !this.selectedClass 
                        || item.classroom_id == this.selectedClass 
                        || item.classroom_id === null;

                    // Match Subject
                    const matchesSubject = !this.selectedSubject 
                        || item.subject_id == this.selectedSubject;

                    return matchesSearch && matchesClass && matchesSubject;
                });
            },

            resetFilters() {
                this.searchQuery = '';
                this.selectedClass = '';
                this.selectedSubject = '';
            }
        }
    }
</script>
@endpush
