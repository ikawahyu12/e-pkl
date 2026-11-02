<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Izin Siswa</title>
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
        
        <!-- Header -->
        

        <div class="p-5">
            <h2 class="text-lg font-bold text-gray-900 mb-1">Ajukan Izin / Sakit</h2>
            <p class="text-sm text-gray-500 mb-5">Ajukan perizinan sesuai format di smartphone.</p>

            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 mb-6">
                <!-- Jenis Perizinan -->
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Jenis Perizinan</label>
                    <select class="w-full border border-gray-300 rounded-lg p-3 text-sm bg-gray-50 outline-none text-gray-700">
                        <option>Sakit</option>
                        <option>Izin</option>
                    </select>
                </div>

                <!-- Tanggal -->
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Mulai Tanggal</label>
                    <input type="date" value="2026-03-13" class="w-full border border-gray-300 rounded-lg p-3 text-sm bg-gray-50 outline-none text-gray-700">
                </div>

                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Hingga Tanggal</label>
                    <input type="date" value="2026-03-15" class="w-full border border-gray-300 rounded-lg p-3 text-sm bg-gray-50 outline-none text-gray-700">
                </div>

                <!-- Alasan -->
                <div class="mb-6">
                    <label class="block text-sm text-gray-600 mb-1">Keterangan / Alasan</label>
                    <textarea placeholder="Berikan alasan jelas ketidakhadiran Anda..." class="w-full border border-gray-300 rounded-lg p-3 text-sm h-24 bg-gray-50 outline-none resize-none"></textarea>
                </div>

                <!-- Upload -->
                <div class="mb-6">
                    <label class="block text-sm text-gray-600 mb-1">Surat Bukti</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 flex flex-col items-center justify-center bg-gray-50 cursor-pointer hover:bg-gray-100 transition">
                        <i class="far fa-file-alt text-blue-500 text-3xl mb-2"></i>
                        <span class="text-sm font-medium text-gray-700">Tarik & lepas foto di sini</span>
                        <span class="text-xs text-gray-400 mt-1">Format file gambar/pdf</span>
                    </div>
                </div>

                <button class="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg hover:bg-blue-700 transition">
                    Kirim Pengajuan
                </button>
            </div>

            <!-- Status Pengajuan Terbaru -->
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Status Pengajuan Terbaru</h3>
            <div class="bg-white border border-gray-200 rounded-xl p-4 flex justify-between items-center shadow-sm">
                <div>
                    <p class="text-sm font-bold text-gray-800">13 - 15 Mar 2026</p>
                    <p class="text-xs text-gray-500 mt-1">Keterangan: Sakit. (Menunggu persetujuan)</p>
                </div>
                <span class="bg-orange-100 text-orange-600 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap">Menunggu</span>
            </div>
        </div>
        
    </div>
</body>
</html>