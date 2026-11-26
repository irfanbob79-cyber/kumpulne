<div class="flex flex-col items-center justify-center p-4 space-y-4 text-center">
    <div class="bg-white p-4 rounded-xl shadow border border-gray-200 inline-block">
        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(240)->margin(1)->generate($url) !!}
    </div>
    <div class="space-y-1">
        <h3 class="font-bold text-lg text-gray-800">{{ $assignment->title }}</h3>
        <p class="text-sm text-gray-500">Pindai kode QR ini dengan kamera HP / WhatsApp siswa untuk langsung membuka form pengumpulan tugas.</p>
    </div>
    <div class="w-full bg-gray-50 p-2 rounded border border-gray-200 text-xs font-mono text-gray-700 break-all select-all">
        {{ $url }}
    </div>
</div>
