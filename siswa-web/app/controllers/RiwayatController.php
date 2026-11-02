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
        $this->render('siswa/riwayat/riwayat', [
            'title'    => 'Riwayat',
            'showBack' => true,
        ]);
    }
}
