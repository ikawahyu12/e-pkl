<?php
/** Jurnal - kerangka halaman (layout & navigasi). Isi fitur dikerjakan anggota yang bertanggung jawab. */
class JurnalController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    public function index()
    {
        $this->render('siswa/jurnal/index', [
            'title'    => 'Jurnal',
            'showBack' => true,
        ]);
    }
}
