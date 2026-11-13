<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class JurnalNativeController extends Controller
{
    public function index()
    {
        // Memanggil file koneksi.php yang ada di folder public
        require public_path('koneksi.php');

        // Query untuk mengambil data kemarin (Opsional, sesuaikan dengan nama tabel Anda)
        $queryKemarin = "SELECT * FROM jurnal_lampiran WHERE DATE(tanggal) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) ORDER BY id DESC LIMIT 1";
        $resultKemarin = mysqli_query($koneksi, $queryKemarin);
        $laporanKemarin = mysqli_fetch_assoc($resultKemarin);

        // Mengirim data ke view
        return view('jurnal.index', compact('laporanKemarin'));
    }

    public function store(Request $request)
    {
        // Memanggil file koneksi.php
        require public_path('koneksi.php');

        $tanggal   = mysqli_real_escape_string($koneksi, $request->input('tanggal'));
        $aktivitas = mysqli_real_escape_string($koneksi, trim($request->input('aktivitas')));

        if (empty($tanggal) || empty($aktivitas)) {
            return redirect()->back()->with('error', 'Tanggal dan aktivitas wajib diisi!');
        }

        if (!$request->hasFile('foto') || !$request->file('foto')->isValid()) {
            return redirect()->back()->with('error', 'Foto dokumentasi wajib diunggah!');
        }

        $file = $request->file('foto');
        $fileExtension = strtolower($file->getClientOriginalExtension());
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        if (!in_array($fileExtension, $allowedExt)) {
            return redirect()->back()->with('error', 'Format file tidak didukung.');
        }
        
        if ($file->getSize() > $maxSize) {
            return redirect()->back()->with('error', 'Ukuran file melebihi 5MB.');
        }

        // Buat nama file unik
        $newFileName = 'jurnal_' . date('YmdHis') . '_' . uniqid() . '.' . $fileExtension;
        
        // Pindahkan file ke folder public/uploads
        $file->move(public_path('uploads'), $newFileName);
        
        // Path untuk disimpan di database
        $pathFile = 'uploads/' . $newFileName;
        $mimeType = $file->getMimeType();
        $ukuranByte = $file->getSize();

        // 1. Simpan ke tabel utama (Sesuaikan nama tabelnya, misal: jurnal)
        $queryJurnal = "INSERT INTO jurnal (tanggal, aktivitas) VALUES ('$tanggal', '$aktivitas')";
        
        if (mysqli_query($koneksi, $queryJurnal)) {
            $jurnal_id = mysqli_insert_id($koneksi);
            
            // 2. Simpan ke tabel file (Sesuai screenshot struktur tabel Anda)
            $queryFile = "INSERT INTO jurnal_files (jurnal_id, nama_file, path_file, mime_type, ukuran_byte) 
                          VALUES ('$jurnal_id', '$newFileName', '$pathFile', '$mimeType', '$ukuranByte')";
            
            if (mysqli_query($koneksi, $queryFile)) {
                return redirect()->back()->with('success', 'Jurnal dan foto berhasil disimpan!');
            } else {
                return redirect()->back()->with('error', 'Gagal simpan detail file: ' . mysqli_error($koneksi));
            }
        } else {
            return redirect()->back()->with('error', 'Gagal simpan jurnal: ' . mysqli_error($koneksi));
        }
    }
}