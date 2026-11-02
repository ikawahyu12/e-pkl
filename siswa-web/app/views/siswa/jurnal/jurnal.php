<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="flex justify-center">
    
    <!-- Container Mobile -->
    <div class="w-full max-w-md bg-white min-h-screen relative shadow-xl">
        
        

        <!-- Konten Form Jurnal -->
        <div class="p-5">
            <h2 class="text-xl font-bold text-gray-900 mb-1">Jurnal Harian Magang</h2>
            <p class="text-sm text-gray-500 mb-6">Isi aktivitas harian Anda, 12 Mar 2026</p>

            <div class="border border-gray-200 rounded-xl p-5 shadow-sm mb-6">
                <h3 class="font-semibold text-gray-800 mb-4">Tulis Laporan Baru</h3>
                
                <!-- Input Tanggal -->
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Tanggal</label>
                    <div class="flex items-center border border-gray-300 rounded-lg p-3 bg-gray-50">
                        <i class="far fa-calendar-alt text-gray-400 mr-3"></i>
                        <input type="date" value="2026-03-12" class="bg-transparent outline-none text-gray-700 w-full text-sm">
                    </div>
                </div>

                <!-- Input Aktivitas -->
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Pekerjaan / Aktivitas</label>
                    <textarea placeholder="Tuliskan di sini apa saja pekerjaan magang yang Anda lakukan hari ini..." class="w-full border border-gray-300 rounded-lg p-3 text-sm h-32 outline-none focus:border-blue-500 resize-none bg-gray-50"></textarea>
                </div>

                <!-- Upload File -->
                <div class="mb-6">
                    <label class="block text-sm text-gray-600 mb-1">Foto Dokumentasi</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 flex flex-col items-center justify-center bg-gray-50 cursor-pointer hover:bg-gray-100 transition">
                        <i class="fas fa-cloud-upload-alt text-blue-500 text-3xl mb-2"></i>
                        <span class="text-sm font-medium text-gray-700">Tarik & lepas foto di sini</span>
                        <span class="text-xs text-gray-400 mt-1">Format file gambar/pdf</span>
                    </div>
                </div>

                <button class="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg hover:bg-blue-700 transition">
                    Kirim Jurnal
                </button>
            </div>

            <!-- Riwayat Bawah -->
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Laporan Jurnal Kemarin</h3>
            <div class="border border-gray-200 rounded-xl p-4 flex justify-between items-center bg-white">
                <div>
                    <p class="text-sm font-bold text-gray-800">Rabu, 11 Maret 2026</p>
                    <p class="text-xs text-gray-500 mt-1">Lihat detail aktivitas</p>
                </div>
                <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">Selesai</span>
            </div>
        </div>
        
    </div>
</body>
</html>