<?php
/** Profil Siswa - kerangka halaman (layout & navigasi). Isi fitur dikerjakan anggota yang bertanggung jawab. */
class ProfilController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    public function index()
    {
        $this->render('siswa/profil/index', [
            'title'    => 'Profil Siswa',
            'showBack' => true,
        ]);
    }
}
