<?php
/** Riwayat - kerangka halaman (layout & navigasi). Isi fitur dikerjakan anggota yang bertanggung jawab. */
class RiwayatController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    public function index()
    {
        $tanggalMulaiPkl = $_SESSION['user']['tanggal_mulai'] ?? '';

        $this->render('siswa/riwayat/riwayat', [
            'title'    => 'Riwayat',
            'showBack' => true,
            'wide'     => true,
            'tanggalMulaiPkl' => $tanggalMulaiPkl,
        ]);
    }
}
