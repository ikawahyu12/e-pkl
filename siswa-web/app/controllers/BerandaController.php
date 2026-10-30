<?php
class BerandaController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    public function index()
    {
        $u = current_user();

        // TODO: ambil data asli dari model (anggota Beranda). Sekarang data dummy.
        $data = [
            'title'    => 'Beranda',
            'showBack' => false,
            'wide'     => true,
            'nama'     => $u['nama'],
            'instansi' => $u['instansi'],
            'statusPresensi' => 'Belum presensi',
            'statusTone'     => 'warn',          // ok | warn | danger | info
            'statistik' => ['Hadir' => '18 Hari', 'Telat' => '2 Kali', 'Izin' => '1 Hari', 'Jurnal' => '18 Data'],
            'minggu'    => [['Sen', 'ok', 90], ['Sel', 'ok', 90], ['Rab', 'izin', 40], ['Kam', 'ok', 90], ['Jum', 'ok', 90]],
        ];
        $this->render('siswa/beranda/index', $data);
    }
}
