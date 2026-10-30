<?php
/** Presensi Harian - kerangka halaman (layout & navigasi). Isi fitur dikerjakan anggota yang bertanggung jawab. */
class PresensiController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    public function index()
    {
        $this->render('siswa/presensi/index', [
            'title'    => 'Presensi Harian',
            'showBack' => true,
        ]);
    }
}
