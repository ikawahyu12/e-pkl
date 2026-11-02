<?php
/** Izin - kerangka halaman (layout & navigasi). Isi fitur dikerjakan anggota yang bertanggung jawab. */
class IzinController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    public function index()
    {
        $this->render('siswa/izin/izin', [
            'title'    => 'Izin',
            'showBack' => true,
        ]);
    }
}
