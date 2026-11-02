<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="flex justify-center">

    <!-- Container Mobile -->
    <div class="w-full max-w-md bg-gray-50 min-h-screen relative shadow-xl pb-10">
        
        

        <div class="p-5 pt-4">
            <h2 class="text-lg font-bold text-gray-900 mb-1">Riwayat PKL Siswa</h2>
            <p class="text-sm text-gray-500 mb-4">Rekap aktivitas dan status kehadiran Anda</p>

            <!-- Tabs -->
            <div class="flex space-x-2 mb-4">
                <button class="flex-1 bg-blue-600 text-white text-sm font-medium py-2 rounded-full shadow-sm">Semua</button>
                <button class="flex-1 bg-white border border-gray-200 text-gray-600 text-sm font-medium py-2 rounded-full shadow-sm">Presensi</button>
                <button class="flex-1 bg-white border border-gray-200 text-gray-600 text-sm font-medium py-2 rounded-full shadow-sm">Jurnal</button>
            </div>

            <!-- Filter Dropdown -->
            <div class="flex space-x-2 mb-6">
                <select class="flex-1 bg-white border border-gray-200 rounded-lg p-2 text-sm text-gray-700 outline-none shadow-sm">
                    <option>Minggu Ini</option>
                </select>
                <select class="flex-1 bg-white border border-gray-200 rounded-lg p-2 text-sm text-gray-700 outline-none shadow-sm">
                    <option>Bulan -</option>
                </select>
            </div>

            <!-- List Riwayat -->
            <div class="space-y-3">
                <!-- Item 1 -->
                <div class="bg-white border border-gray-200 rounded-xl p-4 flex justify-between items-center shadow-sm">
                    <div>
                        <p class="text-sm font-bold text-gray-800">Kamis, 12 Mar 2026</p>
                        <p class="text-xs text-gray-500 mt-1">Presensi: Masuk</p>
                    </div>
                    <div class="text-right">
                        <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-md inline-block mb-1">Masuk</span>
                        <p class="text-xs text-gray-500">07:42 WIB</p>
                    </div>
                </div>
                
                <!-- Item 2 -->
                <div class="bg-white border border-gray-200 rounded-xl p-4 flex justify-between items-center shadow-sm">
                    <div>
                        <p class="text-sm font-bold text-gray-800">Rabu, 11 Mar 2026</p>
                        <p class="text-xs text-gray-500 mt-1">Laporan Jurnal Harian</p>
                    </div>
                    <div class="text-right">
                        <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-1 rounded-md inline-block mb-1">Terverifikasi</span>
                        <p class="text-xs text-gray-500">11:21 WIB</p>
                    </div>
                </div>

                <!-- Item 3 -->
                <div class="bg-white border border-gray-200 rounded-xl p-4 flex justify-between items-center shadow-sm">
                    <div>
                        <p class="text-sm font-bold text-gray-800">Rabu, 11 Mar 2026</p>
                        <p class="text-xs text-gray-500 mt-1">Presensi: Pulang</p>
                    </div>
                    <div class="text-right">
                        <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-md inline-block mb-1">Pulang</span>
                        <p class="text-xs text-gray-500">17:02 WIB</p>
                    </div>
                </div>
                
                <!-- Item 4 -->
                <div class="bg-white border border-gray-200 rounded-xl p-4 flex justify-between items-center shadow-sm">
                    <div>
                        <p class="text-sm font-bold text-gray-800">Senin, 09 Mar 2026</p>
                        <p class="text-xs text-gray-500 mt-1">Presensi: Masuk</p>
                    </div>
                    <div class="text-right">
                        <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-md inline-block mb-1">Masuk</span>
                        <p class="text-xs text-gray-500">08:12 WIB</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</body>
</html>