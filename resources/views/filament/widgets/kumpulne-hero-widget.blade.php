<x-filament-widgets::widget>
    <div style="position: relative; overflow: hidden; border-radius: 1.5rem; background: linear-gradient(125deg, #047857 0%, #16a34a 52%, #0f766e 100%); padding: 2rem; color: #ffffff; box-shadow: 0 20px 35px -10px rgba(4, 120, 87, 0.28);">
        <!-- Decorative blobs -->
        <div style="position: absolute; right: -40px; top: -50px; width: 240px; height: 240px; border-radius: 9999px; background: rgba(255,255,255,0.08); filter: blur(24px); pointer-events: none;"></div>
        <div style="position: absolute; right: 120px; bottom: -60px; width: 180px; height: 180px; border-radius: 9999px; background: rgba(52,211,153,0.14); filter: blur(20px); pointer-events: none;"></div>

        <div style="position: relative; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.5rem;">
            <div style="max-width: 640px;">
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.85rem; border-radius: 9999px; background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.22); font-size: 0.75rem; font-weight: 600; color: #ecfdf5; margin-bottom: 0.75rem;">
                    <span>📁</span>
                    <span>SMKN 1 Labuhan Maringgai • Panel Manajemen Guru</span>
                </div>

                <h2 style="font-size: 1.65rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1.25; margin: 0 0 0.5rem 0; color: #ffffff;">
                    Halo, {{ $userName }}! 👋
                </h2>

                <p style="font-size: 0.875rem; color: rgba(236, 253, 245, 0.92); line-height: 1.55; margin: 0;">
                    @if($isDriveConnected)
                        Google Drive aktif terhubung ke <strong>{{ $driveEmail }}</strong>. Seluruh tugas siswa otomatis tersimpan rapi sesuai folder kelas &amp; mata pelajaran.
                    @else
                        Saat ini pengumpulan masih berjalan dalam <strong>Mode Lokal (Fallback)</strong>. Hubungkan akun Google Drive agar file siswa langsung tersimpan ke cloud.
                    @endif
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div style="display: flex; flex-wrap: wrap; gap: 0.65rem;">
                <a href="{{ route('filament.admin.resources.assignments.create') }}"
                   style="display: inline-flex; align-items: center; gap: 0.45rem; background: #ffffff; color: #065f46; font-weight: 700; font-size: 0.8rem; padding: 0.65rem 1.1rem; border-radius: 0.85rem; text-decoration: none; box-shadow: 0 6px 15px rgba(0,0,0,0.12); transition: transform 0.15s ease;">
                    <span>+ Buat Folder Tugas</span>
                </a>

                <a href="{{ route('filament.admin.resources.drive-accounts.index') }}"
                   style="display: inline-flex; align-items: center; gap: 0.45rem; background: rgba(255,255,255,0.16); color: #ffffff; border: 1px solid rgba(255,255,255,0.3); font-weight: 600; font-size: 0.8rem; padding: 0.65rem 1.05rem; border-radius: 0.85rem; text-decoration: none;">
                    <span>☁️ Kelola Google Drive</span>
                </a>

                <a href="{{ route('portal.index') }}" target="_blank"
                   style="display: inline-flex; align-items: center; gap: 0.45rem; background: rgba(255,255,255,0.1); color: #ecfdf5; border: 1px solid rgba(255,255,255,0.2); font-weight: 600; font-size: 0.8rem; padding: 0.65rem 1rem; border-radius: 0.85rem; text-decoration: none;">
                    <span>🌐 Portal Siswa ↗</span>
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
