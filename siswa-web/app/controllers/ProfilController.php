<?php
/** Profil Siswa - kerangka halaman (layout & navigasi). Isi fitur dikerjakan anggota yang bertanggung jawab. */
class ProfilController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    /** Halaman profil: url('profil') */
    public function index()
    {
        $this->render('siswa/profil/index', [
            'title'    => 'Profil Siswa',
            'showBack' => true,
            // TODO (anggota fitur): 'siswa' => [...data dari database...]
            // Selama 'siswa' belum dikirim, view memakai data dummy (_dummy.php).
        ]);
    }

    /** Form edit profil: url('profil/edit') */
    public function edit()
    {
        $this->render('siswa/profil/edit', [
            'title'    => 'Edit Profil',
            'showBack' => true,
            // TODO: 'siswa' => [...data dari database...]
        ]);
    }

    /** Proses simpan edit profil (POST): url('profil/update') */
    public function update()
    {
        // TODO (anggota fitur): validasi + simpan nama_lengkap, no_hp, alamat, foto ke database.
        header('Location: ' . url('profil'));
        exit;
    }

    /** Form ganti password: url('profil/password') */
    public function password()
    {
        $this->render('siswa/profil/password', [
            'title'    => 'Ganti Password',
            'showBack' => true,
        ]);
    }

    /** Proses simpan password baru (POST): url('profil/updatePassword') */
    public function updatePassword()
    {
        // TODO (anggota fitur): cek password == confirm_password, password_hash(), simpan ke database.
        header('Location: ' . url('profil'));
        exit;
    }
}